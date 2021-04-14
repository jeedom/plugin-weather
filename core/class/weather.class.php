<?php

/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class weather extends eqLogic {
	/*     * *************************Attributs****************************** */
	public static $_widgetPossibility = array('custom' => true, 'custom::layout' => false);
	
	/*     * ***********************Methode static*************************** */
	
	public static function pull($_options) {
		$weather = weather::byId($_options['weather_id']);
		if (is_object($weather) && $weather->getIsEnable() == 1) {
			if (jeedom::isDateOk()) {
				$sunrise = $weather->getCmd(null, 'sunrise')->execCmd();
				$sunset = $weather->getCmd(null, 'sunset')->execCmd();
				if ($sunrise < 500 || $sunrise > 1000) {
					$sunrise = 500;
				}
				if ($sunset > 2300 || $sunset < 1600) {
					$sunset = 1600;
				}
				if ((date('Gi') + 100) >= $sunrise && (date('Gi') + 100) < $sunset) {
					$weather->getCmd(null, 'sunrise')->event($sunrise);
				} else {
					$weather->getCmd(null, 'sunset')->event($sunset);
				}
			}
			$weather->reschedule();
		} else {
			$cron = cron::byClassAndFunction('weather', 'pull', $_options);
			if (is_object($cron)) {
				$cron->remove();
			}
		}
	}
	
	public static function cronDaily() {
		foreach (self::byType('weather') as $weather) {
			if ($weather->getIsEnable() == 1) {
				$cron = cron::byClassAndFunction('weather', 'pull', array('weather_id' => intval($weather->getId())));
				if (!is_object($cron)) {
					$weather->reschedule();
				} else {
					try {
						$c = new Cron\CronExpression(checkAndFixCron($cron->getSchedule()), new Cron\FieldFactory);
						if (!$c->isDue()) {
							$next = $c->getNextRunDate();
							if($next->getTimestamp() > (strtotime('now') + 50000)){
								$weather->reschedule();
							}
						}
					} catch (Exception $ex) {
						if ($c->getPreviousRunDate()->getTimestamp() < (strtotime('now') - 300)) {
							$weather->reschedule();
						}
					}
				}
			}
		}
	}
	
	public static function cron30($_eqLogic_id = null) {
		if ($_eqLogic_id == null) {
			$eqLogics = self::byType('weather', true);
		} else {
			$eqLogics = array(self::byId($_eqLogic_id));
		}
		foreach ($eqLogics as $weather) {
			try {
				$weather->updateWeatherData();
			} catch (Exception $e) {
				log::add('weather', 'info', $e->getMessage());
			}
		}
	}
	
	public static function getIconFromCondition($_condition_id, $_sunrise = null, $_sunset = null) {
		if ($_condition_id >= 200 && $_condition_id <= 299) {
			return 'meteo-orage';
		}
		if (($_condition_id >= 300 && $_condition_id <= 399)) {
			return 'meteo-brouillard';
		}
		if ($_condition_id >= 500 && $_condition_id <= 510) {
			return 'meteo-nuage-soleil-pluie';
		}
		if ($_condition_id >= 520 && $_condition_id <= 599) {
			return 'meteo-pluie';
		}
		if (($_condition_id >= 600 && $_condition_id <= 699) || ($_condition_id == 511)) {
			return 'meteo-neige';
		}
		if ($_condition_id >= 700 && $_condition_id < 770){
			return 'meteo-brouillard';
		}
		if ($_condition_id >= 770 && $_condition_id < 799){
			return 'meteo-vent';
		}
		if ($_condition_id > 800 && $_condition_id <= 899) {
			if ($_sunrise == null || (date('Gi') >= $_sunrise && date('Gi') < $_sunset)) {
				return 'meteo-nuageux';
			} else {
				return 'meteo-nuit-nuage';
			}
		}
		if ($_condition_id == 800) {
			if ($_sunrise == null || (date('Gi') >= $_sunrise && date('Gi') < $_sunset)) {
				return 'meteo-soleil';
			} else {
				return 'far fa-moon';
			}
		}
		if ($_sunrise == null || (date('Gi') >= $_sunrise && date('Gi') < $_sunset)) {
			return 'meteo-soleil';
		} else {
			return 'far fa-moon';
		}
	}
	
	/*     * *********************Methode d'instance************************* */
	public function preSave(){
		if($this->getConfiguration('lat') == ''){
			$this->setConfiguration('lat',config::byKey('info::latitude'));
		}
		if($this->getConfiguration('long') == ''){
			$this->setConfiguration('long',config::byKey('info::longitude'));
		}
	}
	
	public function preInsert() {
		$this->setCategory('heating', 1);
	}
	
	public function postUpdate() {
		$weatherCmd = $this->getCmd(null, 'temperature');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Température', __FILE__));
		$weatherCmd->setLogicalId('temperature');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setUnite('°C');
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->setDisplay('generic_type', 'WEATHER_TEMPERATURE');
		$weatherCmd->save();
		
		$weatherCmd = $this->getCmd(null, 'temperature_feel');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Température ressentie', __FILE__));
		$weatherCmd->setLogicalId('temperature_feel');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setUnite('°C');
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->save();
		
		$weatherCmd = $this->getCmd(null, 'visibility');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Visibilité', __FILE__));
		$weatherCmd->setLogicalId('visibility');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->save();
		
		$weatherCmd = $this->getCmd(null, 'humidity');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Humidité', __FILE__));
		$weatherCmd->setLogicalId('humidity');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setUnite('%');
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->setDisplay('generic_type', 'WEATHER_HUMIDITY');
		$weatherCmd->save();
		
		$weatherCmd = $this->getCmd(null, 'pressure');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Pression', __FILE__));
		$weatherCmd->setLogicalId('pressure');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setUnite('Pa');
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->setDisplay('generic_type', 'WEATHER_PRESSURE');
		$weatherCmd->save();
		
		$weatherCmd = $this->getCmd(null, 'wind_speed');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Vitesse du vent', __FILE__));
		$weatherCmd->setLogicalId('wind_speed');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setUnite('km/h');
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->setDisplay('generic_type', 'WEATHER_WIND_SPEED');
		$weatherCmd->save();
		
		$weatherCmd = $this->getCmd(null, 'wind_direction');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Direction du vent', __FILE__));
		$weatherCmd->setLogicalId('wind_direction');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setUnite('°');
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->setDisplay('generic_type', 'WEATHER_WIND_DIRECTION');
		$weatherCmd->save();
		
		$weatherCmd = $this->getCmd(null, 'sunset');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Coucher du soleil', __FILE__));
		$weatherCmd->setLogicalId('sunset');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setUnite('');
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->setConfiguration('repeatEventManagement', 'always');
		$weatherCmd->setDisplay('generic_type', 'WEATHER_SUNSET');
		$weatherCmd->save();
		
		$weatherCmd = $this->getCmd(null, 'sunrise');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Lever du soleil', __FILE__));
		$weatherCmd->setLogicalId('sunrise');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setUnite('');
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->setDisplay('generic_type', 'WEATHER_SUNRISE');
		$weatherCmd->setConfiguration('repeatEventManagement', 'always');
		$weatherCmd->save();
		
		$weatherCmd = $this->getCmd(null, 'temperature_min');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Température Min', __FILE__));
		$weatherCmd->setLogicalId('temperature_min');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setUnite('°C');
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->setDisplay('generic_type', 'WEATHER_TEMPERATURE_MIN');
		$weatherCmd->save();
		
		$weatherCmd = $this->getCmd(null, 'temperature_max');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Température Max', __FILE__));
		$weatherCmd->setLogicalId('temperature_max');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setUnite('°C');
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->setDisplay('generic_type', 'WEATHER_TEMPERATURE_MAX');
		$weatherCmd->save();
		
		$weatherCmd = $this->getCmd(null, 'condition');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Condition', __FILE__));
		$weatherCmd->setLogicalId('condition');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setUnite('');
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('string');
		$weatherCmd->setDisplay('generic_type', 'WEATHER_CONDITION');
		$weatherCmd->save();
		
		$weatherCmd = $this->getCmd(null, 'condition_id');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Numéro condition', __FILE__));
		$weatherCmd->setLogicalId('condition_id');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setUnite('');
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->setDisplay('generic_type', 'WEATHER_CONDITION_ID');
		$weatherCmd->save();
		
		$weatherCmd = $this->getCmd(null, 'rain');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Pluie', __FILE__));
		$weatherCmd->setLogicalId('rain');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setUnite('mm');
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->setDisplay('generic_type', 'WEATHER_RAIN');
		$weatherCmd->save();
		
		$weatherCmd = $this->getCmd(null, 'snow');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Neige', __FILE__));
		$weatherCmd->setLogicalId('snow');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setUnite('mm');
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->setDisplay('generic_type', 'WEATHER_RAIN');
		$weatherCmd->save();
		
		$weatherCmd = $this->getCmd(null, 'dew_point');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Point de rosée', __FILE__));
		$weatherCmd->setLogicalId('dew_point');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setUnite('°C');
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->save();
		
		$weatherCmd = $this->getCmd(null, 'cloud');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Nuage', __FILE__));
		$weatherCmd->setLogicalId('cloud');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->save();
		
		$weatherCmd = $this->getCmd(null, 'uv');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('UV', __FILE__));
		$weatherCmd->setLogicalId('uv');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->save();
		
		for($i=1;$i<7;$i++){
			$weatherCmd = $this->getCmd(null, 'wind_speed_'.$i);
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('Vitesse du vent +', __FILE__).$i);
			$weatherCmd->setLogicalId('wind_speed_'.$i);
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setUnite('km/h');
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->setDisplay('generic_type', 'WEATHER_WIND_SPEED');
			$weatherCmd->save();
			
			$weatherCmd = $this->getCmd(null, 'wind_direction_'.$i);
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('Direction du vent +', __FILE__).$i);
			$weatherCmd->setLogicalId('wind_direction_'.$i);
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setUnite('°');
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->setDisplay('generic_type', 'WEATHER_WIND_DIRECTION');
			$weatherCmd->save();
			
			
			$weatherCmd = $this->getCmd(null, 'temperature_feel_'.$i);
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('Température ressentie +', __FILE__).$i);
			$weatherCmd->setLogicalId('temperature_feel_'.$i);
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setUnite('°C');
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->save();			
			
			$weatherCmd = $this->getCmd(null, 'pressure_'.$i);
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('Pression +', __FILE__).$i);
			$weatherCmd->setLogicalId('pressure_'.$i);
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setUnite('Pa');
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->save();
			
			$weatherCmd = $this->getCmd(null, 'cloud_'.$i);
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('Nuage +', __FILE__).$i);
			$weatherCmd->setLogicalId('cloud_'.$i);
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->save();			
			
			$weatherCmd = $this->getCmd(null, 'dew_point_'.$i);
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('Point de rosée +', __FILE__).$i);
			$weatherCmd->setLogicalId('dew_point_'.$i);
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setUnite('°C');
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->save();
			
			$weatherCmd = $this->getCmd(null, 'snow_'.$i);
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('Neige +1', __FILE__).$i);
			$weatherCmd->setLogicalId('snow_'.$i);
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setUnite('mm');
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->save();
			
			$weatherCmd = $this->getCmd(null, 'uv_'.$i);
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('UV +', __FILE__).$i);
			$weatherCmd->setLogicalId('uv_'.$i);
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->save();
			
			$weatherCmd = $this->getCmd(null, 'temperature_'.$i.'_min');
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('Température Min +', __FILE__).$i);
			$weatherCmd->setLogicalId('temperature_'.$i.'_min');
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setUnite('°C');
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->setDisplay('generic_type', 'WEATHER_TEMPERATURE_MIN_'.$i);
			$weatherCmd->save();
			
			$weatherCmd = $this->getCmd(null, 'temperature_'.$i.'_max');
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('Température Max +', __FILE__).$i);
			$weatherCmd->setLogicalId('temperature_'.$i.'_max');
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setUnite('°C');
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->setDisplay('generic_type', 'WEATHER_TEMPERATURE_MAX_'.$i);
			$weatherCmd->save();
			
			$weatherCmd = $this->getCmd(null, 'condition_id_'.$i);
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('Numéro condition +', __FILE__).$i);
			$weatherCmd->setLogicalId('condition_id_'.$i);
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setUnite('');
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->setDisplay('generic_type', 'WEATHER_CONDITION_ID_'.$i);
			$weatherCmd->save();
			
			$weatherCmd = $this->getCmd(null, 'rain_'.$i);
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('Pluie +', __FILE__).$i);
			$weatherCmd->setLogicalId('rain_'.$i);
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setUnite('mm');
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->setDisplay('generic_type', 'WEATHER_RAIN_'.$i);
			$weatherCmd->save();
		}
		
		$refresh = $this->getCmd(null, 'refresh');
		if (!is_object($refresh)) {
			$refresh = new weatherCmd();
			$refresh->setName(__('Rafraichir', __FILE__));
		}
		$refresh->setEqLogic_id($this->getId());
		$refresh->setLogicalId('refresh');
		$refresh->setType('action');
		$refresh->setSubType('other');
		$refresh->save();
		
		if ($this->getIsEnable() != 1) {
			$cron = cron::byClassAndFunction('weather', 'pull', array('weather_id' => intval($this->getId())));
			if (is_object($cron)) {
				$cron->remove();
			}
		}
	}
	
	public function preRemove() {
		$cron = cron::byClassAndFunction('weather', 'pull', array('weather_id' => intval($this->getId())));
		if (is_object($cron)) {
			$cron->remove();
		}
	}
	
	public function reschedule() {
		$sunrise = $this->getCmd(null, 'sunrise')->execCmd();
		$sunset = $this->getCmd(null, 'sunset')->execCmd();
		if ($sunrise == '' || !is_numeric($sunrise) || $sunrise < 500 || $sunrise > 1000) {
			$sunrise = 500;
		}
		if ($sunset == '' || !is_numeric($sunset) || $sunset > 2300 || $sunset < 1600) {
			$sunset = 1600;
		}
		$next = null;
		if ((date('Gi') + 10) > $sunrise && (date('Gi') + 10) < $sunset) {
			$next = $sunset;
		} else {
			$next = $sunrise;
		}
		if ($next == null || $next == '' || !is_numeric($next)) {
			return;
		}
		
		if ($next < (date('Gi') + 10)) {
			if (strlen($next) == 3) {
				$next = date('Y-m-d', strtotime('+1 day' . date('Y-m-d'))) . ' 0' . substr($next, 0, 1) . ':' . substr($next, 1, 3);
			} else {
				$next = date('Y-m-d', strtotime('+1 day' . date('Y-m-d'))) . ' ' . substr($next, 0, 2) . ':' . substr($next, 2, 4);
			}
		} else {
			if (strlen($next) == 3) {
				$next = date('Y-m-d') . ' 0' . substr($next, 0, 1) . ':' . substr($next, 1, 3);
			} else {
				$next = date('Y-m-d') . ' ' . substr($next, 0, 2) . ':' . substr($next, 2, 4);
			}
		}
		$cron = cron::byClassAndFunction('weather', 'pull', array('weather_id' => intval($this->getId())));
		if ($next != null) {
			if (!is_object($cron)) {
				$cron = new cron();
				$cron->setClass('weather');
				$cron->setFunction('pull');
				$cron->setOption(array('weather_id' => intval($this->getId())));
				$cron->setLastRun(date('Y-m-d H:i:s'));
			}
			$next = strtotime($next);
			$cron->setSchedule(date('i', $next) . ' ' . date('H', $next) . ' ' . date('d', $next) . ' ' . date('m', $next) . ' * ' . date('Y', $next));
			$cron->save();
		} else {
			if (is_object($cron)) {
				$cron->remove();
			}
		}
	}
	
	public function toHtml($_version = 'dashboard') {
		$replace = $this->preToHtml($_version);
		if (!is_array($replace)) {
			return $replace;
		}
		$version = jeedom::versionAlias($_version);
		$replace['#forecast#'] = '';
		if ($version != 'mobile' || $this->getConfiguration('fullMobileDisplay', 0) == 1) {
			if ($this->getConfiguration('modeImage', 0) == 1) {
				$forcast_template = getTemplate('core', $version, 'forecastIMG', 'weather');
			} else {
				$forcast_template = getTemplate('core', $version, 'forecast', 'weather');
			}
			for ($i = 0; $i < 5; $i++) {
				$replaceDay = array();
				$replaceDay['#day#'] = date_fr(date('l', strtotime('+' . $i . ' days')));
				
				if ($i == 0) {
					$temperature_min = $this->getCmd(null, 'temperature_min');
				} else {
					$temperature_min = $this->getCmd(null, 'temperature_' . $i . '_min');
				}
				$replaceDay['#low_temperature#'] = is_object($temperature_min) ? $temperature_min->execCmd() : '';
				
				if ($i == 0) {
					$temperature_max = $this->getCmd(null, 'temperature_max');
				} else {
					$temperature_max = $this->getCmd(null, 'temperature_' . $i . '_max');
				}
				$replaceDay['#hight_temperature#'] = is_object($temperature_max) ? $temperature_max->execCmd() : '';
				$replaceDay['#tempid#'] = is_object($temperature_max) ? $temperature_max->getId() : '';
				if ($i == 0) {
					$condition = $this->getCmd(null, 'condition_id');
				} else {
					$condition = $this->getCmd(null, 'condition_id_' . $i);
				}
				$replaceDay['#icone#'] = is_object($condition) ? self::getIconFromCondition($condition->execCmd()) : '';
				$replaceDay['#conditionid#'] = is_object($condition) ? $condition->getId() : '';
				$replace['#forecast#'] .= template_replace($replaceDay, $forcast_template);
			}
		}
		$temperature = $this->getCmd(null, 'temperature');
		$replace['#temperature#'] = is_object($temperature) ? $temperature->execCmd() : '';
		$replace['#tempid#'] = is_object($temperature) ? $temperature->getId() : '';
		
		$humidity = $this->getCmd(null, 'humidity');
		$replace['#humidity#'] = is_object($humidity) ? $humidity->execCmd() : '';
		
		$pressure = $this->getCmd(null, 'pressure');
		$replace['#pressure#'] = is_object($pressure) ? $pressure->execCmd() : '';
		$replace['#pressureid#'] = is_object($pressure) ? $pressure->getId() : '';
		
		$wind_speed = $this->getCmd(null, 'wind_speed');
		$replace['#windspeed#'] = is_object($wind_speed) ? $wind_speed->execCmd() : '';
		$replace['#windid#'] = is_object($wind_speed) ? $wind_speed->getId() : '';
		
		$sunrise = $this->getCmd(null, 'sunrise');
		$replace['#sunrise#'] = is_object($sunrise) ? $sunrise->execCmd() : '';
		$replace['#sunid#'] = is_object($sunrise) ? $sunrise->getId() : '';
		if (strlen($replace['#sunrise#']) == 3) {
			$replace['#sunrise#'] = substr($replace['#sunrise#'], 0, 1) . ':' . substr($replace['#sunrise#'], 1, 2);
		} else if (strlen($replace['#sunrise#']) == 4) {
			$replace['#sunrise#'] = substr($replace['#sunrise#'], 0, 2) . ':' . substr($replace['#sunrise#'], 2, 2);
		}
		
		$sunset = $this->getCmd(null, 'sunset');
		$replace['#sunset#'] = is_object($sunset) ? $sunset->execCmd() : '';
		if (strlen($replace['#sunset#']) == 3) {
			$replace['#sunset#'] = substr($replace['#sunset#'], 0, 1) . ':' . substr($replace['#sunset#'], 1, 2);
		} else if (strlen($replace['#sunset#']) == 4) {
			$replace['#sunset#'] = substr($replace['#sunset#'], 0, 2) . ':' . substr($replace['#sunset#'], 2, 2);
		}
		
		$wind_direction = $this->getCmd(null, 'wind_direction');
		$replace['#wind_direction#'] = is_object($wind_direction) ? $wind_direction->execCmd() : 0;
		
		$refresh = $this->getCmd(null, 'refresh');
		$replace['#refresh_id#'] = is_object($refresh) ? $refresh->getId() : '';
		
		$sunset_time = is_object($sunset) ? $sunset->execCmd() : null;
		$sunrise_time = is_object($sunrise) ? $sunrise->execCmd() : null;
		$condition_id = $this->getCmd(null, 'condition_id');
		if (is_object($condition_id)) {
			$replace['#icone#'] = self::getIconFromCondition($condition_id->execCmd(), $sunrise_time, $sunset_time);
		} else {
			$replace['#icone#'] = '';
		}
		
		$condition = $this->getCmd(null, 'condition');
		if (is_object($condition)) {
			$replace['#condition#'] = $condition->execCmd();
			$replace['#conditionid#'] = $condition->getId();
		} else {
			$replace['#condition#'] = '';
			$replace['#collectDate#'] = '';
		}
		if ($this->getConfiguration('modeImage', 0) == 1) {
			return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'currentIMG', 'weather')));
		} else {
			return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'current', 'weather')));
		}
	}
	
	public function updateWeatherData() {
		if ($this->getConfiguration('lat') == '' || $this->getConfiguration('long') == '') {
			throw new Exception(__('La latitude et la longitude ne peut être vide', __FILE__));
		}
		$url = config::byKey('service::cloud::url').'/service/openweathermap';
		$url .= '?lat='.$this->getConfiguration('lat');
		$url .= '&long='.$this->getConfiguration('long');
		$url .= '&lang='.substr(config::byKey('language'),0,2);
		$request_http = new com_http($url);
		$request_http->setHeader(array('Autorization: '.sha512(mb_strtolower(config::byKey('market::username')).':'.config::byKey('market::password'))));
		$datas = json_decode($request_http->exec(10),true);
		log::add('weather', 'debug',json_encode($datas));
		if($datas['state'] != 'ok'){
			return;
		}
		
		$changed = false;
		$changed = $this->checkAndUpdateCmd('temperature', $datas['data']['today']['temperature']['value']) || $changed;
		$changed = $this->checkAndUpdateCmd('humidity', $datas['data']['today']['humidity']['value']) || $changed;
		$changed = $this->checkAndUpdateCmd('pressure', $datas['data']['today']['pressure']['value']) || $changed;
		$changed = $this->checkAndUpdateCmd('condition', $datas['data']['today']['description']) || $changed;
		$changed = $this->checkAndUpdateCmd('condition_id', $datas['data']['today']['summary_id']) || $changed;
		$changed = $this->checkAndUpdateCmd('wind_speed', $datas['data']['today']['wind']['speed']) || $changed;
		$changed = $this->checkAndUpdateCmd('wind_direction', $datas['data']['today']['wind']['deg']) || $changed;
		$changed = $this->checkAndUpdateCmd('rain', $datas['data']['today']['rain']['value']) || $changed;
		$changed = $this->checkAndUpdateCmd('temperature_min', $datas['data']['today']['temperature']['min']) || $changed;
		$changed = $this->checkAndUpdateCmd('temperature_max', $datas['data']['today']['temperature']['max']) || $changed;
		
		$this->checkAndUpdateCmd('temperature_feel', $datas['data']['today']['temperature']['feels']);
		$this->checkAndUpdateCmd('visibility', $datas['data']['today']['visibility']['value']);
		$this->checkAndUpdateCmd('dew_point', $datas['data']['today']['dew_point']['value']);
		$this->checkAndUpdateCmd('snow', $datas['data']['today']['snow']['value']);
		$this->checkAndUpdateCmd('uv', $datas['data']['today']['uv']['value']);
		$this->checkAndUpdateCmd('clouds', $datas['data']['today']['clouds']['all']);
		
		$cmd = $this->getCmd('info', 'sunrise');
		if (is_object($cmd) && $cmd->execCmd() != date('Gi',$datas['data']['today']['sun']['rise'])) {
			$cmd->setCache('value', date('Gi',$datas['data']['today']['sun']['rise']));
			$cmd->setCache('collectDate', date('Y-m-d H:i:s'));
		}
		
		$cmd = $this->getCmd('info', 'sunset');
		if (is_object($cmd) && $cmd->execCmd() != date('Gi',$datas['data']['today']['sun']['set'])) {
			$cmd->setCache('value', date('Gi',$datas['data']['today']['sun']['set']));
			$cmd->setCache('collectDate', date('Y-m-d H:i:s'));
		}
		
		for ($i = 1; $i < 7; $i++) {
			$date = date('Y-m-d', strtotime('+' . $i . ' day'));
			$changed = $this->checkAndUpdateCmd('temperature_' . $i . '_min', $datas['data']['day +'.$i]['temperature']['min']) || $changed;
			$changed = $this->checkAndUpdateCmd('temperature_' . $i . '_max', $datas['data']['day +'.$i]['temperature']['max']) || $changed;
			$changed = $this->checkAndUpdateCmd('condition_' . $i, $datas['data']['day +'.$i]['description']) || $changed;
			$changed = $this->checkAndUpdateCmd('condition_id_' . $i, $datas['data']['day +'.$i]['summary_id']) || $changed;
			$changed = $this->checkAndUpdateCmd('rain_' . $i, $datas['data']['day +'.$i]['rain']['value']) || $changed;
			
			$this->checkAndUpdateCmd('temperature_' . $i, $datas['data']['day +'.$i]['temperature']['value']);
			$this->checkAndUpdateCmd('temperature_feels_' . $i, $datas['data']['day +'.$i]['temperature']['feels']);
			$this->checkAndUpdateCmd('pressure_' . $i, $datas['data']['day +'.$i]['pressure']['value']);
			$this->checkAndUpdateCmd('clouds_' . $i, $datas['data']['day +'.$i]['clouds']['all']);
			$this->checkAndUpdateCmd('uv_' . $i, $datas['data']['day +'.$i]['uv']['value']);
			$this->checkAndUpdateCmd('snow_' . $i, $datas['data']['day +'.$i]['snow']['value']);
			$this->checkAndUpdateCmd('dew_point_' . $i, $datas['data']['day +'.$i]['dew_point']['value']);
			$this->checkAndUpdateCmd('wind_speed_' . $i, $datas['data']['day +'.$i]['wind']['speed']);
			$this->checkAndUpdateCmd('wind_direction_' . $i, $datas['data']['day +'.$i]['wind']['deg']);
		}
		if ($changed) {
			$this->refreshWidget();
		}
	}
	
}

class weatherCmd extends cmd {
	/*     * *************************Attributs****************************** */
	
	public static $_widgetPossibility = array('custom' => false);
	
	/*     * ***********************Methode static*************************** */
	
	/*     * *********************Methode d'instance************************* */
	
	public function execute($_options = array()) {
		if ($this->getLogicalId() == 'refresh') {
			$this->getEqLogic()->updateWeatherData();
		}
		return false;
	}
	
	/*     * **********************Getteur Setteur*************************** */
}
