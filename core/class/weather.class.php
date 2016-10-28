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
use Cmfcmf\OpenWeatherMap;

require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class weather extends eqLogic {
	/*     * *************************Attributs****************************** */
	public static $_widgetPossibility = array('custom' => true);

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

	public static function cronHourly() {
		foreach (self::byType('weather') as $weather) {
			if ($weather->getIsEnable() == 1) {
				$cron = cron::byClassAndFunction('weather', 'pull', array('weather_id' => intval($weather->getId())));
				if (!is_object($cron)) {
					$weather->reschedule();
				} else {
					try {
						$c = new Cron\CronExpression($cron->getSchedule(), new Cron\FieldFactory);
						if (!$c->isDue()) {
							$c->getNextRunDate();
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
			if ($_sunrise == null || (date('Gi') >= $_sunrise && date('Gi') < $_sunset)) {
				return 'meteo-orage';
			} else {
				return 'meteo-orage';
			}
		}
		if ($_condition_id >= 300 && $_condition_id <= 399) {
			if ($_sunrise == null || (date('Gi') >= $_sunrise && date('Gi') < $_sunset)) {
				return 'meteo-brouillard';
			} else {
				return 'meteo-brouillard';
			}
		}
		if ($_condition_id >= 500 && $_condition_id <= 599) {
			if ($_sunrise == null || (date('Gi') >= $_sunrise && date('Gi') < $_sunset)) {
				return 'meteo-pluie';
			} else {
				return 'meteo-pluie';
			}
		}
		if ($_condition_id >= 600 && $_condition_id <= 699) {
			if ($_sunrise == null || (date('Gi') >= $_sunrise && date('Gi') < $_sunset)) {
				return 'meteo-neige';
			} else {
				return 'meteo-neige';
			}
		}
		if ($_condition_id >= 700 && $_condition_id <= 799) {
			if ($_sunrise == null || (date('Gi') >= $_sunrise && date('Gi') < $_sunset)) {
				return 'meteo-vent';
			} else {
				return 'meteo-vent';
			}
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
				return 'fa fa-moon-o';
			}
		}
		if ($_sunrise == null || (date('Gi') >= $_sunrise && date('Gi') < $_sunset)) {
			return 'meteo-soleil';
		} else {
			return 'fa fa-moon-o';
		}
	}

	/*     * *********************Methode d'instance************************* */
	public function preInsert() {
		$this->setCategory('heating', 1);
	}

	public function preUpdate() {
		if ($this->getConfiguration('city') == '') {
			throw new Exception(__('L\identifiant de la ville ne peut être vide', __FILE__));
		}
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
		$weatherCmd->setUnite('');
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('string');
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

		$weatherCmd = $this->getCmd(null, 'temperature_1_min');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Température Min +1', __FILE__));
		$weatherCmd->setLogicalId('temperature_1_min');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setUnite('°C');
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->setDisplay('generic_type', 'WEATHER_TEMPERATURE_MIN_1');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'temperature_1_max');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Température Max +1', __FILE__));
		$weatherCmd->setLogicalId('temperature_1_max');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setUnite('°C');
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->setDisplay('generic_type', 'WEATHER_TEMPERATURE_MAX_1');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'temperature_2_min');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Température Min +2', __FILE__));
		$weatherCmd->setLogicalId('temperature_2_min');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setUnite('°C');
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->setDisplay('generic_type', 'WEATHER_TEMPERATURE_MIN_2');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'temperature_2_max');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Température Max +2', __FILE__));
		$weatherCmd->setLogicalId('temperature_2_max');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setUnite('°C');
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->setDisplay('generic_type', 'WEATHER_TEMPERATURE_MAX_2');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'temperature_3_min');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Température Min +3', __FILE__));
		$weatherCmd->setLogicalId('temperature_3_min');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setUnite('°C');
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->setDisplay('generic_type', 'WEATHER_TEMPERATURE_MIN_3');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'temperature_3_max');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Température Max +3', __FILE__));
		$weatherCmd->setLogicalId('temperature_3_max');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setUnite('°C');
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->setDisplay('generic_type', 'WEATHER_TEMPERATURE_MAX_3');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'temperature_4_min');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Température Min +4', __FILE__));
		$weatherCmd->setLogicalId('temperature_4_min');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setUnite('°C');
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->setDisplay('generic_type', 'WEATHER_TEMPERATURE_MIN_4');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'temperature_4_max');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Température Max +4', __FILE__));
		$weatherCmd->setLogicalId('temperature_4_max');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setUnite('°C');
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->setDisplay('generic_type', 'WEATHER_TEMPERATURE_MAX_4');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'condition_1');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Condition +1', __FILE__));
		$weatherCmd->setLogicalId('condition_1');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setUnite('');
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('string');
		$weatherCmd->setDisplay('generic_type', 'WEATHER_CONDITION_1');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'condition_id_1');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Numéro condition +1', __FILE__));
		$weatherCmd->setLogicalId('condition_id_1');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setUnite('');
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->setDisplay('generic_type', 'WEATHER_CONDITION_ID_1');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'condition_2');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Condition +2', __FILE__));
		$weatherCmd->setLogicalId('condition_2');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setUnite('');
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('string');
		$weatherCmd->setDisplay('generic_type', 'WEATHER_CONDITION_2');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'condition_id_2');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Numéro condition +2', __FILE__));
		$weatherCmd->setLogicalId('condition_id_2');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setUnite('');
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->setDisplay('generic_type', 'WEATHER_CONDITION_ID_2');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'condition_3');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Condition +3', __FILE__));
		$weatherCmd->setLogicalId('condition_3');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setUnite('');
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('string');
		$weatherCmd->setDisplay('generic_type', 'WEATHER_CONDITION_3');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'condition_id_3');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Numéro condition +3', __FILE__));
		$weatherCmd->setLogicalId('condition_id_3');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setUnite('');
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->setDisplay('generic_type', 'WEATHER_CONDITION_ID_3');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'condition_4');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Condition +4', __FILE__));
		$weatherCmd->setLogicalId('condition_4');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setUnite('');
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('string');
		$weatherCmd->setDisplay('generic_type', 'WEATHER_CONDITION_4');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'condition_id_4');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Numéro condition +4', __FILE__));
		$weatherCmd->setLogicalId('condition_id_4');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setUnite('');
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->setDisplay('generic_type', 'WEATHER_CONDITION_ID_4');
		$weatherCmd->save();

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

		if ($this->getIsEnable() == 1) {
			$this->updateWeatherData();
		} else {
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
		if ((date('Gi') + 100) > $sunrise && (date('Gi') + 100) < $sunset) {
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
			$forcast_template = getTemplate('core', $version, 'forecast', 'weather');
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
		return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'current', 'weather')));
	}

	public function updateWeatherData() {
		if ($this->getConfiguration('city') == '') {
			throw new Exception(__('La ville ne peut être vide', __FILE__));
		}
		$owm = new OpenWeatherMap(trim(config::byKey('apikey', 'weather')));
		$weather = $owm->getWeather($this->getConfiguration('city'), 'metric', 'fr');
		if ($weather == NULL) {
			return;
		}
		log::add('weather', 'debug', print_r($weather, true));
		$changed = false;
		$changed = $this->checkAndUpdateCmd('temperature', round($weather->temperature->now->getValue(), 1)) || $changed;
		$changed = $this->checkAndUpdateCmd('humidity', $weather->humidity->getValue()) || $changed;
		$changed = $this->checkAndUpdateCmd('pressure', $weather->pressure->getValue()) || $changed;
		$changed = $this->checkAndUpdateCmd('condition', ucfirst($weather->weather->description)) || $changed;
		$changed = $this->checkAndUpdateCmd('condition_id', $weather->weather->id) || $changed;
		$changed = $this->checkAndUpdateCmd('wind_speed', $weather->wind->speed->getValue() * 3.6) || $changed;
		$changed = $this->checkAndUpdateCmd('wind_direction', $weather->wind->direction->getValue()) || $changed;

		$timezone = config::byKey('timezone', 'core', 'Europe/Brussels');
		$cmd = $this->getCmd('info', 'sunrise');
		if (is_object($cmd) && isset($weather->sun->rise) && $weather->sun->rise instanceof DateTime && $cmd->execCmd() != $weather->sun->rise->setTimezone(new \DateTimezone($timezone))->format('Gi')) {
			cache::set('cmd' . $cmd->getId(), $weather->sun->rise->setTimezone(new \DateTimezone('Europe/Berlin'))->format('Gi'), 0);
		}

		$cmd = $this->getCmd('info', 'sunset');
		if (is_object($cmd) && isset($weather->sun->set) && $weather->sun->set instanceof DateTime && $cmd->execCmd() != $weather->sun->set->setTimezone(new \DateTimezone($timezone))->format('Gi')) {
			cache::set('cmd' . $cmd->getId(), $weather->sun->set->setTimezone(new \DateTimezone('Europe/Berlin'))->format('Gi'), 0);
		}
		$forecast = $owm->getWeatherForecast($this->getConfiguration('city'), 'metric', 'fr', '', 4);

		for ($i = 0; $i < 5; $i++) {
			$date = date('Y-m-d', strtotime('+' . $i . ' day'));
			$maxTemp = null;
			$minTemp = null;
			$condition_id = null;
			$condition = null;
			foreach ($forecast as $weather) {
				$sDate = $weather->time->day->format('Y-m-d');
				if ($date != $sDate) {
					continue;
				}
				if ($maxTemp == null || $maxTemp < round($weather->temperature->max->getValue(), 1)) {
					$maxTemp = round($weather->temperature->max->getValue(), 1);
				}
				if ($minTemp == null || $minTemp > round($weather->temperature->min->getValue(), 1)) {
					$minTemp = round($weather->temperature->min->getValue(), 1);
				}
				$condition_id = $weather->weather->id;
				$condition = ucfirst($weather->weather->description);
			}
			if ($i == 0) {
				if ($minTemp != null) {
					$changed = $this->checkAndUpdateCmd('temperature_min', $minTemp) || $changed;
				}
				if ($maxTemp != null) {
					$changed = $this->checkAndUpdateCmd('temperature_max', $maxTemp) || $changed;
				}
				continue;
			}
			if ($minTemp != null) {
				$changed = $this->checkAndUpdateCmd('temperature_' . $i . '_min', $minTemp) || $changed;
			}
			if ($maxTemp != null) {
				$changed = $this->checkAndUpdateCmd('temperature_' . $i . '_max', $maxTemp) || $changed;
			}
			if ($condition != null) {
				$changed = $this->checkAndUpdateCmd('condition_' . $i, $condition) || $changed;
			}
			if ($condition_id != null) {
				$changed = $this->checkAndUpdateCmd('condition_id_' . $i, $condition_id) || $changed;
			}
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
