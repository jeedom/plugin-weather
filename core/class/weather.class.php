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

	private $_collectDate = '';
	private $_weatherData = '';

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
						$c->getNextRunDate();
					} catch (Exception $ex) {
						$weather->reschedule();
					}
				}
			}
		}
	}

	public static function updateWeatherData($_options) {
		$weather = weather::byId($_options['weather_id']);
		if (is_object($weather)) {
			if ($weather->getIsEnable() == 1) {
				foreach ($weather->getCmd('info') as $cmd) {
					if ($cmd->getLogicalId() != 'sunset' && $cmd->getLogicalId() != 'sunrise') {
						$value = $cmd->execute();
						if ($value != $cmd->execCmd()) {
							$cmd->setCollectDate('');
							$cmd->event($value);
						}
					} else if ($cmd->getLogicalId() != 'sunset') {
						$result = $cmd->execute();
						if ($result !== false) {
							cache::set('cmd' . $cmd->getId(), $cmd->execute(), 0);
						}
					} else if ($cmd->getLogicalId() != 'sunrise') {
						$result = $cmd->execute();
						if ($result !== false) {
							cache::set('cmd' . $cmd->getId(), $cmd->execute(), 0);
						}
					}
				}
			}
			$mc = cache::byKey('weatherWidgetmobile' . $weather->getId());
			$mc->remove();
			$mc = cache::byKey('weatherWidgetdashboard' . $weather->getId());
			$mc->remove();
			$weather->toHtml('mobile');
			$weather->toHtml('dashboard');
			$weather->refreshWidget();
		}
	}

	public function refreshWidget() {
		nodejs::pushUpdate('eventEqLogic', $this->getId());
	}

	public static function getIconFromCondition($_condition, $_sunrise = null, $_sunset = null) {
		if (strpos(strtolower($_condition), __('orage', __FILE__)) !== false || strpos(strtolower($_condition), __('storm', __FILE__)) !== false) {
			if ($_sunrise == null || (date('Gi') >= $_sunrise && date('Gi') < $_sunset)) {
				return 'meteo-orage';
			} else {
				return 'meteo-orage';
			}
		}
		if (strpos(strtolower($_condition), __('brouillard', __FILE__)) !== false || strpos(strtolower($_condition), __('brumeux', __FILE__)) !== false || strpos(strtolower($_condition), __('fog', __FILE__)) !== false) {
			if ($_sunrise == null || (date('Gi') >= $_sunrise && date('Gi') < $_sunset)) {
				return 'meteo-brouillard';
			} else {
				return 'meteo-brouillard';
			}
		}
		if (strpos(strtolower($_condition), __('pluie', __FILE__)) !== false || strpos(strtolower($_condition), __('rain', __FILE__)) !== false) {
			if ($_sunrise == null || (date('Gi') >= $_sunrise && date('Gi') < $_sunset)) {
				return 'meteo-pluie';
			} else {
				return 'meteo-pluie';
			}
		}
		if (strpos(strtolower($_condition), __('averse', __FILE__)) !== false || strpos(strtolower($_condition), __('shower', __FILE__)) !== false) {
			if ($_sunrise == null || (date('Gi') >= $_sunrise && date('Gi') < $_sunset)) {
				return 'meteo-pluie';
			} else {
				return 'meteo-pluie';
			}
		}
		if (strpos(strtolower($_condition), __('nuage', __FILE__)) !== false || strpos(strtolower($_condition), __('cloud', __FILE__)) !== false) {
			if ($_sunrise == null || (date('Gi') >= $_sunrise && date('Gi') < $_sunset)) {
				return 'meteo-nuageux';
			} else {
				return 'meteo-nuit-nuage';
			}
		}
		if (strpos(strtolower($_condition), __('soleil', __FILE__)) !== false || strpos(strtolower($_condition), __('sun', __FILE__)) !== false) {
			if ($_sunrise == null || (date('Gi') >= $_sunrise && date('Gi') < $_sunset)) {
				return 'meteo-soleil';
			} else {
				return 'fa fa-moon-o';
			}
		}
		if (strpos(strtolower($_condition), __('dégagé', __FILE__)) !== false || strpos(strtolower($_condition), __('clear', __FILE__)) !== false) {
			if ($_sunrise == null || (date('Gi') >= $_sunrise && date('Gi') < $_sunset)) {
				return 'meteo-soleil';
			} else {
				return 'fa fa-moon-o';
			}
		}
		if (strpos(strtolower($_condition), __('beau', __FILE__)) !== false || strpos(strtolower($_condition), __('fair', __FILE__)) !== false) {
			if ($_sunrise == null || (date('Gi') >= $_sunrise && date('Gi') < $_sunset)) {
				return 'meteo-soleil';
			} else {
				return 'fa fa-moon-o';
			}
		}
		if (strpos(strtolower($_condition), __('pluvieux', __FILE__)) !== false || strpos(strtolower($_condition), __('rain', __FILE__)) !== false) {
			if ($_sunrise == null || (date('Gi') >= $_sunrise && date('Gi') < $_sunset)) {
				return 'meteo-pluie';
			} else {
				return 'meteo-pluie';
			}
		}
		return '';
	}

	public static function convertCondition($_condition) {
		if (translate::getLanguage() == 'fr_FR') {
			switch ($_condition) {
				case 'AM Drizzle':
					return 'Avec Bruine le matin';
				case 'Drizzle':
					return 'Avec Bruine';
				case 'Showers Early':
					return 'Peu nuageux';
				case 'PM Sun':
					return 'Ensoleillé l\'après-midi';
				case 'AM Rain':
					return 'Pluvieux le matin';
				case 'PM Rain':
					return 'Pluvieux l\'après-midi';
				case 'PM Clouds':
					return 'Nuageux l\'après-midi';
				case 'PM Showers':
					return 'Pluvieux l\'après-midi';
				case 'PM Light Rain':
					return 'Legèrement pluvieux l\'après-midi';
				case 'PM Thunderstorms':
					return 'Orageux l\'aprés-midi';
				case 'PM Thunderstorms/Wind':
					return 'Orageux et venteux l\'aprés-midi';
				case 'AM Sun':
					return 'Ensoleillé le matin';
				case 'AM Clouds':
					return 'Nuageux le matin';
				case 'AM Clouds/PM Sun':
					return 'Nuageux le matin/Ensoleillé l\'après-midi';
				case 'AM Fog/PM Sun':
					return 'Brumeux le matin/Ensoleillé l\'après-midi';
				case 'Rain/Wind':
					return 'Pluvieux et venteux';
				case 'AM Sun/PM Clouds':
					return 'Ensoleillé le matin/Nuageux l\'après-midi';
				case 'Clouds Early/Clearing Late':
					return 'Nuageux tôt et eclaircie tardive';
				case 'Partly Cloudy':
					return 'Partiellement nuageux';
				case 'Mostly Cloudy':
					return 'Nuageux';
				case 'Cloudy':
					return 'Nuageux';
				case 'AM Showers/Wind':
					return 'Venteux et pluvieux le matin';
				case 'PM Showers/Wind':
					return 'Venteux et pluvieux l\'après-midi';
				case 'Partly Cloudy/Wind':
					return 'Partiellement nuageux et venteux';
				case 'Fair/Windy':
					return 'Venteux';
				case 'Sunny':
					return 'Ensoleillé';
				case 'Mostly Sunny':
					return 'Plutôt ensoleillé';
				case 'Fair':
					return 'Beau';
				case 'Clear':
					return 'Dégagé';
				case 'Mostly Clear':
					return 'Dégagé';
				case 'Showers':
					return 'Avec des Averses';
				case 'Few showers':
					return 'Avec quelques averses';
				case 'Showers/Wind':
					return 'Averses et vent';
				case 'Light Rain':
					return 'Faiblement pluvieux';
				case 'Rain':
					return 'Pluvieux';
				case 'Fog':
					return 'Brumeux';
				case 'Partial Fog':
					return 'Partiellement brumeux';
				case 'AM Fog/PM Clouds':
					return 'Brumeux le matin et nuageux l\'après-midi';
				case 'Scattered Showers':
					return 'Peu nuageux';
				case 'AM Showers':
					return 'Pluvieux l\'après-midi';
				case 'PM Tunderstorms':
					return 'Orageux l\'après-midi';
				case 'Light Rain with Thunder':
					return 'Légèrement pluvieux avec orage';
				case 'Thunder':
					return 'Orageux';
				case 'Scattered Thunderstorms':
					return 'Orageux';
				case 'Heavy Rain':
					return 'Fortement pluvieux';
				case 'Rain Shower':
					return 'Pluvieux';
				case 'Showers Late':
					return 'Avec Averses tardive';
				case 'Showers in the Vicinity':
					return 'Avec Averses localisées';
				case 'Light Rain Shower':
					return 'légèrement pluvieux';
				case 'Light Drizzle':
					return 'Brumeux';
				case 'Thunder in the Vicinity':
					return 'Orageux localement';
				case 'AM Little Rain':
					return 'Légèrement pluvieux le matin';
				case 'AM Light Rain':
					return 'Légèrement pluvieux le matin';
				case 'Thunderstorms':
					return 'Orageux';
				case 'Rain/Thunder':
					return 'Pluvieux/Orageux';
				case 'Isolated Thunderstorms':
					return 'Orageux localement';
				case 'Scattered Thunderstorms/Wind':
					return 'Avec Orage dispersé et venteux';
				case 'Light Rain/Wind':
					return 'Légèrement pluvieux et venteux';
				case 'Thunderstorms Early':
					return 'Orageux';
				case 'Cloudy/Wind':
					return 'Nuageux et venteux';
				case 'Rain late':
					return 'Pluvieux tardivement';
				case 'PM Light Rain/Wind':
					return 'Légèrement pluvieux et vent';
				case 'Showers Late':
					return 'Tardivement pluvieux';
				case 'Rain Early':
					return 'Pluvieux';
				case 'Mostly Clear/Wind':
					return 'Dégagé et venteux';
				case 'Sunny/Wind':
					return 'Ensoleillé et venteux';
				case 'Rain early':
					return 'Pluvieux';
				case 'Light Rain/Fog':
					return 'Faiblement pluvieux et brumeux';
				case 'Light Rain Early':
					return 'Pluvieux le matin';
				case 'Mostly Sunny/Wind':
					return 'Ensoleillé avec du vent';
				case 'Mist':
					return 'Brumeux';
				case 'Rain/Windy':
					return 'Pluvieux et venteux';
				case 'Light Rain/Windy ':
					return 'Faiblement pluvieux et venteux';
				case 'Mostly Cloudy/Windy':
					return 'Nuageux avec du vent';
				case 'Cloudy/Windy':
					return 'Nuageux et venteux';
				case 'Light Snow':
					return 'Faiblement neigeux';
				case 'Partly Cloudy/Windy':
					return 'Partiellement nuageux et venteux';
				case 'Heavy Drizzle':
					return 'Brumeux';
				case 'Light Rain Late':
					return 'Légèrement pluvieux dans la soirée';
				case 'AM Rain/Snow':
					return 'Pluvieux et neigeux le matin';
				case 'Rain/Snow':
					return 'Pluvieux et neigeux';
				case 'PM Rain/Wind':
					return 'Pluvieux et venteux l\'après midi';
				case 'Rain/Snow Late':
					return 'Pluvieux et neigeux plus tard';
				case 'Rain/Snow Showers':
					return 'Pluvieux et neigeux';
				case 'Shallow Fog':
					return 'Faiblement pluvieux';
				case 'Rain and Snow':
					return 'Pluvieux et neigeux';
				case 'Light Drizzle/Windy':
					return 'Légèrement brumeux et venteux';
				case 'Sleet':
					return 'Neigeux';
				case 'Snow':
					return 'Neigeux';
				case 'Showers Late':
					return 'Pluvieux dans la soirée';
				case 'PM Snow Showers':
					return 'Neigeux l\'après midi';
				case 'Light Rain/Windy':
					return 'Légèrement pluvieux et venteux';
				case 'Haze':
					return 'Brumeux';
				case 'Heavy Rain/Wind':
					return 'Fortement pluvieux et venteux';
				case 'AM Light Rain/Wind':
					return 'Pluvieux et venteux le matin';
				default:
					return $_condition;
			}
		}
		return $_condition;
	}

	/*     * *********************Methode d'instance************************* */

	public function preSave() {
		$weather = $this->getWeatherFromYahooXml();
		$this->setConfiguration('city_name', $weather['location']['city']);
		if ($this->getConfiguration('refreshCron') == '') {
			$this->setConfiguration('refreshCron', '*/30 * * * *');
		}
	}

	public function preUpdate() {
		if ($this->getConfiguration('city') == '') {
			throw new Exception(__('L\identifiant de la ville ne peut être vide', __FILE__));
		}
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
		$weatherCmd->setConfiguration('day', '-1');
		$weatherCmd->setConfiguration('data', 'temp');
		$weatherCmd->setUnite('°C');
		$weatherCmd->setType('info');
		$weatherCmd->setEventOnly(1);
		$weatherCmd->setSubType('numeric');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'humidity');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Humidité', __FILE__));
		$weatherCmd->setLogicalId('humidity');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setConfiguration('day', '-1');
		$weatherCmd->setConfiguration('data', 'humidity');
		$weatherCmd->setUnite('%');
		$weatherCmd->setType('info');
		$weatherCmd->setEventOnly(1);
		$weatherCmd->setSubType('numeric');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'pressure');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Pression', __FILE__));
		$weatherCmd->setLogicalId('pressure');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setConfiguration('day', '-1');
		$weatherCmd->setConfiguration('data', 'pressure');
		$weatherCmd->setUnite('Pa');
		$weatherCmd->setType('info');
		$weatherCmd->setEventOnly(1);
		$weatherCmd->setSubType('numeric');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'condition_now');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Condition Actuelle', __FILE__));
		$weatherCmd->setLogicalId('condition_now');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setConfiguration('day', '-1');
		$weatherCmd->setConfiguration('data', 'condition');
		$weatherCmd->setUnite('');
		$weatherCmd->setType('info');
		$weatherCmd->setEventOnly(1);
		$weatherCmd->setSubType('string');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'wind_speed');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Vitesse du vent', __FILE__));
		$weatherCmd->setLogicalId('wind_speed');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setConfiguration('day', '-1');
		$weatherCmd->setConfiguration('data', 'wind_speed');
		$weatherCmd->setUnite('km/h');
		$weatherCmd->setType('info');
		$weatherCmd->setEventOnly(1);
		$weatherCmd->setSubType('numeric');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'wind_direction');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Direction du vent', __FILE__));
		$weatherCmd->setLogicalId('wind_direction');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setConfiguration('day', '-1');
		$weatherCmd->setConfiguration('data', 'wind_direction');
		$weatherCmd->setUnite('');
		$weatherCmd->setType('info');
		$weatherCmd->setEventOnly(1);
		$weatherCmd->setSubType('string');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'sunset');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Coucher du soleil', __FILE__));
		$weatherCmd->setLogicalId('sunset');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setConfiguration('day', '-1');
		$weatherCmd->setConfiguration('data', 'sunset');
		$weatherCmd->setUnite('');
		$weatherCmd->setType('info');
		$weatherCmd->setEventOnly(1);
		$weatherCmd->setSubType('numeric');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'sunrise');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Lever du soleil', __FILE__));
		$weatherCmd->setLogicalId('sunrise');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setConfiguration('day', '-1');
		$weatherCmd->setConfiguration('data', 'sunrise');
		$weatherCmd->setUnite('');
		$weatherCmd->setType('info');
		$weatherCmd->setEventOnly(1);
		$weatherCmd->setSubType('numeric');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'temperature_min');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Température Min', __FILE__));
		$weatherCmd->setLogicalId('temperature_min');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setConfiguration('day', '0');
		$weatherCmd->setConfiguration('data', 'low');
		$weatherCmd->setUnite('°C');
		$weatherCmd->setType('info');
		$weatherCmd->setEventOnly(1);
		$weatherCmd->setSubType('numeric');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'temperature_max');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Température Max', __FILE__));
		$weatherCmd->setLogicalId('temperature_max');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setConfiguration('day', '0');
		$weatherCmd->setConfiguration('data', 'high');
		$weatherCmd->setUnite('°C');
		$weatherCmd->setType('info');
		$weatherCmd->setEventOnly(1);
		$weatherCmd->setSubType('numeric');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'condition');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Condition', __FILE__));
		$weatherCmd->setLogicalId('condition');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setConfiguration('day', '0');
		$weatherCmd->setConfiguration('data', 'condition');
		$weatherCmd->setUnite('');
		$weatherCmd->setType('info');
		$weatherCmd->setEventOnly(1);
		$weatherCmd->setSubType('string');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'temperature_1_min');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Température Min +1', __FILE__));
		$weatherCmd->setLogicalId('temperature_1_min');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setConfiguration('day', '1');
		$weatherCmd->setConfiguration('data', 'low');
		$weatherCmd->setUnite('°C');
		$weatherCmd->setType('info');
		$weatherCmd->setEventOnly(1);
		$weatherCmd->setSubType('numeric');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'temperature_1_max');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Température Max +1', __FILE__));
		$weatherCmd->setLogicalId('temperature_1_max');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setConfiguration('day', '1');
		$weatherCmd->setConfiguration('data', 'high');
		$weatherCmd->setUnite('°C');
		$weatherCmd->setType('info');
		$weatherCmd->setEventOnly(1);
		$weatherCmd->setSubType('numeric');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'temperature_2_min');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Température Min +2', __FILE__));
		$weatherCmd->setLogicalId('temperature_2_min');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setConfiguration('day', '2');
		$weatherCmd->setConfiguration('data', 'low');
		$weatherCmd->setUnite('°C');
		$weatherCmd->setType('info');
		$weatherCmd->setEventOnly(1);
		$weatherCmd->setSubType('numeric');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'temperature_2_max');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Température Max +2', __FILE__));
		$weatherCmd->setLogicalId('temperature_2_max');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setConfiguration('day', '2');
		$weatherCmd->setConfiguration('data', 'high');
		$weatherCmd->setUnite('°C');
		$weatherCmd->setType('info');
		$weatherCmd->setEventOnly(1);
		$weatherCmd->setSubType('numeric');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'temperature_3_min');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Température Min +3', __FILE__));
		$weatherCmd->setLogicalId('temperature_3_min');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setConfiguration('day', '3');
		$weatherCmd->setConfiguration('data', 'low');
		$weatherCmd->setUnite('°C');
		$weatherCmd->setType('info');
		$weatherCmd->setEventOnly(1);
		$weatherCmd->setSubType('numeric');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'temperature_3_max');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Température Max +3', __FILE__));
		$weatherCmd->setLogicalId('temperature_3_max');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setConfiguration('day', '3');
		$weatherCmd->setConfiguration('data', 'high');
		$weatherCmd->setUnite('°C');
		$weatherCmd->setType('info');
		$weatherCmd->setEventOnly(1);
		$weatherCmd->setSubType('numeric');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'temperature_4_min');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Température Min +4', __FILE__));
		$weatherCmd->setLogicalId('temperature_4_min');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setConfiguration('day', '4');
		$weatherCmd->setConfiguration('data', 'low');
		$weatherCmd->setUnite('°C');
		$weatherCmd->setType('info');
		$weatherCmd->setEventOnly(1);
		$weatherCmd->setSubType('numeric');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'temperature_4_max');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Température Max +4', __FILE__));
		$weatherCmd->setLogicalId('temperature_4_max');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setConfiguration('day', '4');
		$weatherCmd->setConfiguration('data', 'high');
		$weatherCmd->setUnite('°C');
		$weatherCmd->setType('info');
		$weatherCmd->setEventOnly(1);
		$weatherCmd->setSubType('numeric');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'condition_1');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Condition +1', __FILE__));
		$weatherCmd->setLogicalId('condition_1');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setConfiguration('day', '1');
		$weatherCmd->setConfiguration('data', 'condition');
		$weatherCmd->setUnite('');
		$weatherCmd->setType('info');
		$weatherCmd->setEventOnly(1);
		$weatherCmd->setSubType('string');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'condition_2');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Condition +2', __FILE__));
		$weatherCmd->setLogicalId('condition_2');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setConfiguration('day', '2');
		$weatherCmd->setConfiguration('data', 'condition');
		$weatherCmd->setUnite('');
		$weatherCmd->setType('info');
		$weatherCmd->setEventOnly(1);
		$weatherCmd->setSubType('string');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'condition_3');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Condition +3', __FILE__));
		$weatherCmd->setLogicalId('condition_3');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setConfiguration('day', '3');
		$weatherCmd->setConfiguration('data', 'condition');
		$weatherCmd->setUnite('');
		$weatherCmd->setType('info');
		$weatherCmd->setEventOnly(1);
		$weatherCmd->setSubType('string');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'condition_4');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Condition +4', __FILE__));
		$weatherCmd->setLogicalId('condition_4');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setConfiguration('day', '4');
		$weatherCmd->setConfiguration('data', 'condition');
		$weatherCmd->setUnite('');
		$weatherCmd->setType('info');
		$weatherCmd->setEventOnly(1);
		$weatherCmd->setSubType('string');
		$weatherCmd->save();

		if ($this->getIsEnable() == 1) {
			$this->reschedule();
			foreach ($this->getCmd('info') as $cmd) {
				if ($cmd->getSubType() == 'numeric' && strpos($cmd->getLogicalId(), 'temperature_') === false) {
					$cmd->setIsHistorized($this->getConfiguration('historize', 0));
					$cmd->save();
				}
			}
			$cron = cron::byClassAndFunction('weather', 'updateWeatherData', array('weather_id' => intval($this->getId())));
			if (!is_object($cron)) {
				$cron = new cron();
				$cron->setClass('weather');
				$cron->setFunction('updateWeatherData');
				$cron->setOption(array('weather_id' => intval($this->getId())));
			}
			$cron->setSchedule($this->getConfiguration('refreshCron', '*/30 * * * *'));
			$cron->save();
			self::updateWeatherData(array('weather_id' => intval($this->getId())));
		} else {
			$cron = cron::byClassAndFunction('weather', 'pull', array('weather_id' => intval($this->getId())));
			if (is_object($cron)) {
				$cron->remove();
			}
			$cron = cron::byClassAndFunction('weather', 'updateWeatherData', array('weather_id' => intval($this->getId())));
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
		$cron = cron::byClassAndFunction('weather', 'updateWeatherData', array('weather_id' => intval($this->getId())));
		if (is_object($cron)) {
			$cron->remove();
		}
	}

	public function reschedule() {
		$sunrise = $this->getCmd(null, 'sunrise')->execCmd();
		$sunset = $this->getCmd(null, 'sunset')->execCmd();
		if ($sunrise < 500 || $sunrise > 1000) {
			$sunrise = 500;
		}
		if ($sunset > 2300 || $sunset < 1600) {
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
		if ($this->getIsEnable() != 1) {
			return '';
		}
		if (!$this->hasRight('r')) {
			return '';
		}
		$_version = jeedom::versionAlias($_version);
		$mc = cache::byKey('weatherWidget' . $_version . $this->getId());
		if ($mc->getValue() != '') {
			return $mc->getValue();
		}
		$html_forecast = '';

		if ($_version != 'mobile' || $this->getConfiguration('fullMobileDisplay', 0) == 1) {
			$forcast_template = getTemplate('core', $_version, 'forecast', 'weather');
			for ($i = 0; $i < 5; $i++) {
				$replace = array();
				$replace['#day#'] = date_fr(date('l', strtotime('+' . $i . ' days')));

				if ($i == 0) {
					$temperature_min = $this->getCmd(null, 'temperature_min');
				} else {
					$temperature_min = $this->getCmd(null, 'temperature_' . $i . '_min');
				}
				$replace['#low_temperature#'] = is_object($temperature_min) ? $temperature_min->execCmd() : '';

				if ($i == 0) {
					$temperature_max = $this->getCmd(null, 'temperature_max');
				} else {
					$temperature_max = $this->getCmd(null, 'temperature_' . $i . '_max');
				}
				$replace['#hight_temperature#'] = is_object($temperature_max) ? $temperature_max->execCmd() : '';
				$replace['#tempid#'] = is_object($temperature_max) ? $temperature_max->getId() : '';

				if ($i == 0) {
					$condition = $this->getCmd(null, 'condition');
				} else {
					$condition = $this->getCmd(null, 'condition_' . $i);
				}
				$replace['#icone#'] = is_object($condition) ? self::getIconFromCondition($condition->execCmd()) : '';
				$replace['#conditionid#'] = is_object($condition) ? $condition->getId() : '';

				$html_forecast .= template_replace($replace, $forcast_template);
			}
		}
		$replace = array(
			'#id#' => $this->getId(),
			'#city#' => $this->getConfiguration('city_name'),
			'#collectDate#' => '',
			'#background_color#' => $this->getBackgroundColor($_version),
			'#eqLink#' => $this->getLinkToConfiguration(),
			'#forecast#' => $html_forecast,
		);
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

		$condition = $this->getCmd(null, 'condition_now');
		$sunset_time = is_object($sunset) ? $sunset->execCmd() : null;
		$sunrise_time = is_object($sunrise) ? $sunrise->execCmd() : null;
		if (is_object($condition)) {
			$replace['#icone#'] = self::getIconFromCondition($condition->execCmd(), $sunrise_time, $sunset_time);
			$replace['#condition#'] = $condition->execCmd();
			$replace['#conditionid#'] = $condition->getId();
			$replace['#collectDate#'] = $condition->getCollectDate();
		} else {
			$replace['#icone#'] = '';
			$replace['#condition#'] = '';
			$replace['#collectDate#'] = '';
		}

		$parameters = $this->getDisplay('parameters');
		if (is_array($parameters)) {
			foreach ($parameters as $key => $value) {
				$replace['#' . $key . '#'] = $value;
			}
		}

		$html = template_replace($replace, getTemplate('core', $_version, 'current', 'weather'));
		cache::set('weatherWidget' . $_version . $this->getId(), $html, 0);
		return $html;
	}

	public function getShowOnChild() {
		return true;
	}

	private static function parseXmlWeather($xml) {
		$weather = simplexml_load_string($xml);
		$return = array();
		if (is_object($weather)) {
			$channel_yweather = $weather->channel->children("http://xml.weather.yahoo.com/ns/rss/1.0");
			foreach ($channel_yweather as $x => $channel_item) {
				foreach ($channel_item->attributes() as $k => $attr) {
					$yw_channel[$x][$k] = $attr;
				}
			}
			$item_yweather = $weather->channel->item->children("http://xml.weather.yahoo.com/ns/rss/1.0");
			foreach ($item_yweather as $x => $yw_item) {
				foreach ($yw_item->attributes() as $k => $attr) {
					if ($k == 'day') {
						$day = $attr;
					}
					if ($x == 'forecast') {
						$yw_forecast[$x][$day . ''][$k] = $attr;
					} else {
						$yw_forecast[$x][$k] = $attr;
					}
				}
			}

			$return = array();
			$return['condition']['text'] = (string) $yw_forecast['condition']['text'][0];
			$return['condition']['text'] = self::convertCondition($return['condition']['text']);

			$return['condition']['temperature'] = (string) $yw_forecast['condition']['temp'][0];
			$return['location']['city'] = (string) $yw_channel['location']['city'][0];
			$return['atmosphere']['humidity'] = (string) $yw_channel['atmosphere']['humidity'][0];
			$return['atmosphere']['pressure'] = (string) $yw_channel['atmosphere']['pressure'][0];
			$return['wind']['speed'] = (string) $yw_channel['wind']['speed'][0];
			$return['wind']['direction'] = (string) $yw_channel['wind']['direction'][0];

			$return['astronomy']['sunrise'] = (string) $yw_channel['astronomy']['sunrise'][0];
			$return['astronomy']['sunrise'] = date("Gi", strtotime($return['astronomy']['sunrise']));

			$return['astronomy']['sunset'] = (string) $yw_channel['astronomy']['sunset'][0];
			$return['astronomy']['sunset'] = date("Gi", strtotime($return['astronomy']['sunset']));
			$day = 0;
			foreach ($yw_forecast['forecast'] as $forecast) {
				$return['forecast'][$day]['day'] = (string) $forecast['day'][0];
				$return['forecast'][$day]['day'] = date_fr($return['forecast'][$day]['day']);
				$return['forecast'][$day]['condition'] = (string) $forecast['text'][0];
				$return['forecast'][$day]['condition'] = self::convertCondition($return['forecast'][$day]['condition']);
				$return['forecast'][$day]['low_temperature'] = (string) $forecast['low'][0];
				$return['forecast'][$day]['high_temperature'] = (string) $forecast['high'][0];
				$day++;
			}
		}
		return $return;
	}

	/*     * **********************Getteur Setteur*************************** */

	public function getWeatherFromYahooXml() {
		if ($this->getConfiguration('city') == '') {
			return false;
		}
		if ($this->_weatherData != '' && is_array($this->_weatherData)) {
			return $this->_weatherData;
		}
		$this->setCollectDate(date('Y-m-d H:i:s'));
		try {
			$request = new com_http('http://weather.yahooapis.com/forecastrss?w=' . urlencode($this->getConfiguration('city')) . '&u=c');
			$xml = $request->exec(30000, 2);
			$result = self::parseXmlWeather($xml);
		} catch (Exception $e) {
			log::add('weather', 'info', 'Error on data fetch : ' . $e->getMessage());
			return '';
		}
		$this->_weatherData = $result;
		return $result;
	}

	public function getCollectDate() {
		return $this->_collectDate;
	}

	public function setCollectDate($_collectDate) {
		$this->_collectDate = $_collectDate;
	}

}

class weatherCmd extends cmd {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	/*     * *********************Methode d'instance************************* */

	public function dontRemoveCmd() {
		return true;
	}

	public function execute($_options = array()) {
		$eqLogic_weather = $this->getEqLogic();
		$weather = $eqLogic_weather->getWeatherFromYahooXml();

		if (!is_array($weather)) {
			sleep(1);
			$weather = $eqLogic_weather->getWeatherFromYahooXml();
			if (!is_array($weather)) {
				return false;
			}
		}

		if ($this->getConfiguration('day') == -1) {
			if ($this->getConfiguration('data') == 'condition') {
				return $weather['condition']['text'];
			}
			if ($this->getConfiguration('data') == 'temp') {
				return $weather['condition']['temperature'];
			}
			if ($this->getConfiguration('data') == 'humidity') {
				return $weather['atmosphere']['humidity'];
			}
			if ($this->getConfiguration('data') == 'pressure') {
				return $weather['atmosphere']['pressure'];
			}
			if ($this->getConfiguration('data') == 'wind_speed') {
				return $weather['wind']['speed'];
			}
			if ($this->getConfiguration('data') == 'wind_direction') {
				return $weather['wind']['direction'];
			}
			if ($this->getConfiguration('data') == 'sunrise') {
				return $weather['astronomy']['sunrise'];
			}
			if ($this->getConfiguration('data') == 'sunset') {
				return $weather['astronomy']['sunset'];
			}
		}

		if ($this->getConfiguration('data') == 'condition') {
			return $weather['forecast'][$this->getConfiguration('day')]['condition'];
		}
		if ($this->getConfiguration('data') == 'low') {
			return $weather['forecast'][$this->getConfiguration('day')]['low_temperature'];
		}
		if ($this->getConfiguration('data') == 'high') {
			return $weather['forecast'][$this->getConfiguration('day')]['high_temperature'];
		}
		return false;
	}

	/*     * **********************Getteur Setteur*************************** */
}
