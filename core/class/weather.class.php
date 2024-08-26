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
		$weather = self::byId($_options['weather_id']);
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
			$cron = cron::byClassAndFunction(__CLASS__, 'pull', $_options);
			if (is_object($cron)) {
				$cron->remove();
			}
		}
	}

	public static function cronDaily() {
		foreach (self::byType(__CLASS__) as $weather) {
			if ($weather->getIsEnable() == 1) {
				$cron = cron::byClassAndFunction(__CLASS__, 'pull', array('weather_id' => intval($weather->getId())));
				if (!is_object($cron)) {
					$weather->reschedule();
				} else {
					try {
						$c = new Cron\CronExpression(checkAndFixCron($cron->getSchedule()), new Cron\FieldFactory);
						if (!$c->isDue()) {
							$next = $c->getNextRunDate();
							if ($next->getTimestamp() > (strtotime('now') + 50000)) {
								$weather->reschedule();
							}
						}
					} catch (Exception $ex) {
						if ($c->getPreviousRunDate()->getTimestamp() < (strtotime('now') - 300)) {
							$weather->reschedule();
						}
					}
				}
				if(config::byKey('calculDJU', 'weather') == 1) {
					$temperatureReference = config::byKey('temperatureReference', 'weather');
					$methodeDJU = config::byKey('methodeDJU', 'weather');
					$temperature = $weather->getCmd(null, 'temperature');
					$djuJourEteCmd = $weather->getCmd(null, 'dju_jour_clim');
					$djuJourHiverCmd = $weather->getCmd(null, 'dju_jour_chauffage');
					$djuMoisEteCmd = $weather->getCmd(null, 'dju_mois_clim');
					$djuMoisHiverCmd = $weather->getCmd(null, 'dju_mois_chauffage');
					$djuAnneeEteCmd = $weather->getCmd(null, 'dju_annee_clim');
					$djuAnneeHiverCmd = $weather->getCmd(null, 'dju_annee_chauffage');
					if (is_object($temperature) && is_object($djuJourEteCmd) && is_object($djuJourHiverCmd) && is_object($djuMoisEteCmd) && is_object($djuMoisHiverCmd) && is_object($djuAnneeEteCmd) && is_object($djuAnneeHiverCmd)) {
						// Calcul DJU Mois
						if (date('d') == '1') {
							$debutJourneeM1 = date("Y-m-d 00:00:00", strtotime("-30 days"));
							$finJourneeM1 = date("Y-m-d 23:59:59", strtotime("-30 days"));
	
							$result = weather::calculDJU($methodeDJU, $temperature->getId(), $debutJourneeM1, $finJourneeM1, $temperatureReference);
							$djuHiver = $result[0];
							$djuEte = $result[1];
							$djuMoisHiverCmd->event($djuHiver);
							$djuMoisHiverCmd->save();
							$djuMoisEteCmd->event($djuEte);
							$djuMoisEteCmd->save();
						} 
						
						// Calcul DJU Année
						if (date('z') == '0') {
							$debutJourneeA1 = date("Y-m-d 00:00:00", strtotime("-365 days"));
							$finJourneeA1 = date("Y-m-d 23:59:59", strtotime("-365 days"));
							$result = weather::calculDJU($methodeDJU, $temperature->getId(), $debutJourneeA1, $finJourneeA1, $temperatureReference);
							$djuHiver = $result[0];
							$djuEte = $result[1];
							$djuAnneeHiverCmd->event($djuHiver);
							$djuAnneeHiverCmd->save();
							$djuAnneeEteCmd->event($djuEte);
							$djuAnneeEteCmd->save();
						} 
						
						// Calcul DJU Jour
						$debutJourneeVeille = date("Y-m-d 00:00:00", strtotime("yesterday"));
						$finJourneeVeille = date("Y-m-d 23:59:59", strtotime("yesterday"));
						$result = weather::calculDJU($methodeDJU, $temperature->getId(), $debutJourneeVeille, $finJourneeVeille, $temperatureReference);
						$djuHiver = $result[0];
						$djuEte = $result[1];
						log::add('weather', 'debug', 'mmomo');
						$djuJourHiverCmd->event($djuHiver);
						$djuJourHiverCmd->save();
						$djuJourEteCmd->event($djuEte);
						$djuJourEteCmd->save();
					}
				}
			}
		}
	}

	public static function calculDJU($methodeDJU, $temperatureID, $start, $end, $temperatureReference) {
		if ($methodeDJU == 'meteo') {
			$moyenne = history::getTemporalAvg($temperatureID, $start, $end);
			$djuHiver = max(0, $temperatureReference - $moyenne);
			$djuEte = max(0, $moyenne - $temperatureReference);
		} elseif ($methodeDJU == 'profEnergie') {
			$min = scenarioExpression::minBetween($temperatureID, $start, $end);
			$max = scenarioExpression::maxBetween($temperatureID, $start, $end);
			$djuHiver = ($temperatureReference - $min) * (0.08 + 0.42 * ($temperatureReference - $min) / ($max - $min));
			$djuEte = ($max - $temperatureReference) * (0.08 + 0.42 * ($max - $temperatureReference) / ($max - $min));
		}
		return array($djuHiver, $djuEte);
	}

	public static function cron30($_eqLogic_id = null) {
		if ($_eqLogic_id == null) {
			$eqLogics = self::byType(__CLASS__, true);
		} else {
			$eqLogics = array(self::byId($_eqLogic_id));
		}
		foreach ($eqLogics as $weather) {
			try {
				$weather->updateWeatherData();
			} catch (Exception $e) {
				log::add(__CLASS__, 'info', $e->getMessage());
			}
		}
	}

	public static function cronHourly() {
		if(config::byKey('calculDJU', 'weather') == 1) {
			// Calcul DJU Heure
			$heureActuelle = date("Y-m-d H:i:s");
			$heurePrecedente = date("Y-m-d H:i:s", strtotime("-1 hour"));
			$temperatureReference = config::byKey('temperatureReference', 'weather');
			$methodeDJU = config::byKey('methodeDJU', 'weather');

			foreach (self::byType(__CLASS__) as $weather) {
				if ($weather->getIsEnable() == 1) {
					$temperature = $weather->getCmd(null, 'temperature');
					$djuHeureHiverCmd = $weather->getCmd(null, 'dju_heure_chauffage');
					$djuHeureEteCmd = $weather->getCmd(null, 'dju_heure_clim');
					if(is_object($temperature) && is_object($djuHeureHiverCmd) && is_object($djuHeureEteCmd)) {
						$result = weather::calculDJU($methodeDJU, $temperature->getId(), $heureActuelle, $heurePrecedente, $temperatureReference);
						$djuHiver = $result[0];
						$djuEte = $result[1];
						$djuHeureHiverCmd->event($djuHiver);
						$djuHeureHiverCmd->save();
						$djuHeureEteCmd->event($djuEte);
						$djuHeureEteCmd->save();
					}
				}
			}
		}
	}

	public static function getIconFromCondition($_condition_id, $_sunrise = null, $_sunset = null) {
		if (in_array($_condition_id, array(1087, 1273, 1276, 1279, 1282))) {
			return 'meteo-orage';
		}
		if (in_array($_condition_id, array(1135, 1030, 1072, 1147, 1150, 1153, 1168, 1171))) {
			return 'meteo-brouillard';
		}
		if (in_array($_condition_id, array(1189, 1195, 1063, 1180, 1186, 1201, 1240, 1243, 1246, 1183, 1207, 1198, 1192))) {
			return 'meteo-pluie';
		}
		if (in_array($_condition_id, array(1066, 1069, 1114, 1117, 1204, 1210, 1213, 1216, 1219, 1222, 1225, 1237, 1249, 1252, 1255, 1258, 1261, 1264))) {
			return 'meteo-neige';
		}
		if (in_array($_condition_id, array(1006, 1003, 1009))) {
			if ($_sunrise == null || (date('Gi') >= $_sunrise && date('Gi') < $_sunset)) {
				return 'meteo-nuageux';
			} else {
				return 'meteo-nuit-nuage';
			}
		}
		if ($_sunrise == null || (date('Gi') >= $_sunrise && date('Gi') < $_sunset)) {
			return 'meteo-soleil';
		} else {
			return 'far fa-moon';
		}
	}

	/*     * *********************Methode d'instance************************* */
	public function preSave() {
		if ($this->getConfiguration('lat') == '') {
			$this->setConfiguration('lat', config::byKey('info::latitude'));
		}
		if ($this->getConfiguration('long') == '') {
			$this->setConfiguration('long', config::byKey('info::longitude'));
		}
	}

	public function preInsert() {
		$this->setCategory('heating', 1);
	}

	public function postInsert() {
		$this->updateWeatherData();
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
		$weatherCmd->setIsHistorized(1);
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->setDisplay('generic_type', 'WEATHER_TEMPERATURE');
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

		$weatherCmd = $this->getCmd(null, 'air_quality_co');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Pollution CO', __FILE__));
		$weatherCmd->setLogicalId('air_quality_co');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'air_quality_no2');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Pollution NO2', __FILE__));
		$weatherCmd->setLogicalId('air_quality_no2');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'air_quality_o3');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Pollution O3', __FILE__));
		$weatherCmd->setLogicalId('air_quality_o3');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'air_quality_so2');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Pollution SO2', __FILE__));
		$weatherCmd->setLogicalId('air_quality_so2');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->save();

		$weatherCmd = $this->getCmd(null, 'air_quality_pm2.5');
		if (!is_object($weatherCmd)) {
			$weatherCmd = new weatherCmd();
		}
		$weatherCmd->setName(__('Pollution PM2.5', __FILE__));
		$weatherCmd->setLogicalId('air_quality_pm2.5');
		$weatherCmd->setEqLogic_id($this->getId());
		$weatherCmd->setType('info');
		$weatherCmd->setSubType('numeric');
		$weatherCmd->save();

		if(config::byKey('calculDJU', 'weather') == 1)  {

			$weatherCmd = $this->getCmd(null, 'dju_heure_clim');
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('DJU Horaire Climatisation', __FILE__));
			$weatherCmd->setLogicalId('dju_heure_clim');
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->save();

			$weatherCmd = $this->getCmd(null, 'dju_heure_chauffage');
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('DJU Horaire Chauffage', __FILE__));
			$weatherCmd->setLogicalId('dju_heure_chauffage');
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->save();

			$weatherCmd = $this->getCmd(null, 'dju_jour_clim');
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('DJU Journalier Climatisation', __FILE__));
			$weatherCmd->setLogicalId('dju_jour_clim');
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->save();

			$weatherCmd = $this->getCmd(null, 'dju_jour_chauffage');
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('DJU Journalier Chauffage', __FILE__));
			$weatherCmd->setLogicalId('dju_jour_chauffage');
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->save();

			$weatherCmd = $this->getCmd(null, 'dju_mois_clim');
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('DJU Mensuel Climatisation', __FILE__));
			$weatherCmd->setLogicalId('dju_mois_clim');
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->save();

			$weatherCmd = $this->getCmd(null, 'dju_mois_chauffage');
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('DJU Mensuel Chauffage', __FILE__));
			$weatherCmd->setLogicalId('dju_mois_chauffage');
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->save();

			$weatherCmd = $this->getCmd(null, 'dju_annee_clim');
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('DJU Annuel Climatisation', __FILE__));
			$weatherCmd->setLogicalId('dju_annee_clim');
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->save();

			$weatherCmd = $this->getCmd(null, 'dju_annee_chauffage');
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('DJU Annuel Chauffage', __FILE__));
			$weatherCmd->setLogicalId('dju_annee_chauffage');
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->save();
		}


		for ($i = 1; $i < 4; $i++) {
			$weatherCmd = $this->getCmd(null, 'wind_speed_h' . $i);
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('Vitesse du vent', __FILE__) . ' H+' . $i);
			$weatherCmd->setLogicalId('wind_speed_h' . $i);
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setUnite('km/h');
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->setDisplay('generic_type', 'WEATHER_WIND_SPEED');
			$weatherCmd->save();

			$weatherCmd = $this->getCmd(null, 'wind_direction_h' . $i);
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('Direction du vent', __FILE__) . ' H+' . $i);
			$weatherCmd->setLogicalId('wind_direction_h' . $i);
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setUnite('°');
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->setDisplay('generic_type', 'WEATHER_WIND_DIRECTION');
			$weatherCmd->save();

			$weatherCmd = $this->getCmd(null, 'temperature_h' . $i);
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('Température ', __FILE__) . ' H+' . $i);
			$weatherCmd->setLogicalId('temperature_h' . $i);
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setUnite('°C');
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->setDisplay('generic_type', 'WEATHER_TEMPERATURE');
			$weatherCmd->save();

			$weatherCmd = $this->getCmd(null, 'humidity_h' . $i);
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('Humidité ', __FILE__) . ' H+' . $i);
			$weatherCmd->setLogicalId('humidity_h' . $i);
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setUnite('%');
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->setDisplay('generic_type', 'WEATHER_HUMIDITY');
			$weatherCmd->save();
			
			$weatherCmd = $this->getCmd(null, 'uv_h' . $i);
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('UV', __FILE__) . ' H+' . $i);
			$weatherCmd->setLogicalId('uv_h' . $i);
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->save();

			$weatherCmd = $this->getCmd(null, 'visibility_h' . $i);
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('Visibilité', __FILE__) . ' H+' . $i);
			$weatherCmd->setLogicalId('visibility_h' . $i);
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->save();

			$weatherCmd = $this->getCmd(null, 'condition_id_h' . $i);
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('Numéro condition', __FILE__) . ' H+' . $i);
			$weatherCmd->setLogicalId('condition_id_h' . $i);
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setUnite('');
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->save();

			$weatherCmd = $this->getCmd(null, 'rain_h' . $i);
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('Pluie', __FILE__) . ' H+' . $i);
			$weatherCmd->setLogicalId('rain_h' . $i);
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setUnite('mm');
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->save();

			$weatherCmd = $this->getCmd(null, 'chance_rain_h' . $i);
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('Chance de pluie', __FILE__) . ' H+' . $i);
			$weatherCmd->setLogicalId('chance_rain_h' . $i);
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setUnite('%');
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->save();

			$weatherCmd = $this->getCmd(null, 'snow_h' . $i);
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('Neige', __FILE__) . ' H+' . $i);
			$weatherCmd->setLogicalId('snow_h' . $i);
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setUnite('mm');
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->save();

			$weatherCmd = $this->getCmd(null, 'chance_snow_h' . $i);
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('Chance de neige', __FILE__) . ' H+' . $i);
			$weatherCmd->setLogicalId('chance_snow_h' . $i);
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setUnite('%');
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->save();
		}

		for ($i = 1; $i < 7; $i++) {
			$weatherCmd = $this->getCmd(null, 'wind_speed_' . $i);
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('Vitesse du vent', __FILE__) . ' +' . $i);
			$weatherCmd->setLogicalId('wind_speed_' . $i);
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setUnite('km/h');
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->setDisplay('generic_type', 'WEATHER_WIND_SPEED');
			$weatherCmd->save();

			$weatherCmd = $this->getCmd(null, 'wind_direction_' . $i);
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('Direction du vent', __FILE__) . ' +' . $i);
			$weatherCmd->setLogicalId('wind_direction_' . $i);
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setUnite('°');
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->setDisplay('generic_type', 'WEATHER_WIND_DIRECTION');
			$weatherCmd->save();

			$weatherCmd = $this->getCmd(null, 'uv_' . $i);
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('UV', __FILE__) . ' +' . $i);
			$weatherCmd->setLogicalId('uv_' . $i);
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->save();

			$weatherCmd = $this->getCmd(null, 'temperature_' . $i . '_min');
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('Température Min', __FILE__) . ' +' . $i);
			$weatherCmd->setLogicalId('temperature_' . $i . '_min');
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setUnite('°C');
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->setDisplay('generic_type', 'WEATHER_TEMPERATURE_MIN_' . $i);
			$weatherCmd->save();

			$weatherCmd = $this->getCmd(null, 'temperature_' . $i . '_max');
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('Température Max', __FILE__) . ' +' . $i);
			$weatherCmd->setLogicalId('temperature_' . $i . '_max');
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setUnite('°C');
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->setDisplay('generic_type', 'WEATHER_TEMPERATURE_MAX_' . $i);
			$weatherCmd->save();

			$weatherCmd = $this->getCmd(null, 'condition_id_' . $i);
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('Numéro condition', __FILE__) . ' +' . $i);
			$weatherCmd->setLogicalId('condition_id_' . $i);
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setUnite('');
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->setDisplay('generic_type', 'WEATHER_CONDITION_ID_' . $i);
			$weatherCmd->save();

			$weatherCmd = $this->getCmd(null, 'rain_' . $i);
			if (!is_object($weatherCmd)) {
				$weatherCmd = new weatherCmd();
			}
			$weatherCmd->setName(__('Pluie', __FILE__) . ' +' . $i);
			$weatherCmd->setLogicalId('rain_' . $i);
			$weatherCmd->setEqLogic_id($this->getId());
			$weatherCmd->setUnite('mm');
			$weatherCmd->setType('info');
			$weatherCmd->setSubType('numeric');
			$weatherCmd->setDisplay('generic_type', 'WEATHER_RAIN_' . $i);
			$weatherCmd->save();

			if ($i < 4) {
				$weatherCmd = $this->getCmd(null, 'air_quality_co_' . $i);
				if (!is_object($weatherCmd)) {
					$weatherCmd = new weatherCmd();
				}
				$weatherCmd->setName(__('Pollution CO', __FILE__) . ' +' . $i);
				$weatherCmd->setLogicalId('air_quality_co_' . $i);
				$weatherCmd->setEqLogic_id($this->getId());
				$weatherCmd->setType('info');
				$weatherCmd->setSubType('numeric');
				$weatherCmd->save();

				$weatherCmd = $this->getCmd(null, 'air_quality_no2_' . $i);
				if (!is_object($weatherCmd)) {
					$weatherCmd = new weatherCmd();
				}
				$weatherCmd->setName(__('Pollution NO2', __FILE__) . ' +' . $i);
				$weatherCmd->setLogicalId('air_quality_no2_' . $i);
				$weatherCmd->setEqLogic_id($this->getId());
				$weatherCmd->setType('info');
				$weatherCmd->setSubType('numeric');
				$weatherCmd->save();

				$weatherCmd = $this->getCmd(null, 'air_quality_o3_' . $i);
				if (!is_object($weatherCmd)) {
					$weatherCmd = new weatherCmd();
				}
				$weatherCmd->setName(__('Pollution O3', __FILE__) . ' +' . $i);
				$weatherCmd->setLogicalId('air_quality_o3_' . $i);
				$weatherCmd->setEqLogic_id($this->getId());
				$weatherCmd->setType('info');
				$weatherCmd->setSubType('numeric');
				$weatherCmd->save();

				$weatherCmd = $this->getCmd(null, 'air_quality_so2_' . $i);
				if (!is_object($weatherCmd)) {
					$weatherCmd = new weatherCmd();
				}
				$weatherCmd->setName(__('Pollution SO2', __FILE__) . ' +' . $i);
				$weatherCmd->setLogicalId('air_quality_so2_' . $i);
				$weatherCmd->setEqLogic_id($this->getId());
				$weatherCmd->setType('info');
				$weatherCmd->setSubType('numeric');
				$weatherCmd->save();

				$weatherCmd = $this->getCmd(null, 'air_quality_pm2.5_' . $i);
				if (!is_object($weatherCmd)) {
					$weatherCmd = new weatherCmd();
				}
				$weatherCmd->setName(__('Pollution PM2.5', __FILE__) . ' +' . $i);
				$weatherCmd->setLogicalId('air_quality_pm2.5_' . $i);
				$weatherCmd->setEqLogic_id($this->getId());
				$weatherCmd->setType('info');
				$weatherCmd->setSubType('numeric');
				$weatherCmd->save();

				$weatherCmd = $this->getCmd(null, 'air_quality_pm10_' . $i);
				if (!is_object($weatherCmd)) {
					$weatherCmd = new weatherCmd();
				}
				$weatherCmd->setName(__('Pollution PM10', __FILE__) . ' +' . $i);
				$weatherCmd->setLogicalId('air_quality_pm10_' . $i);
				$weatherCmd->setEqLogic_id($this->getId());
				$weatherCmd->setType('info');
				$weatherCmd->setSubType('numeric');
				$weatherCmd->save();
			}
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
			$cron = cron::byClassAndFunction(__CLASS__, 'pull', array('weather_id' => intval($this->getId())));
			if (is_object($cron)) {
				$cron->remove();
			}
		}
	}

	public function preRemove() {
		$cron = cron::byClassAndFunction(__CLASS__, 'pull', array('weather_id' => intval($this->getId())));
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
		$cron = cron::byClassAndFunction(__CLASS__, 'pull', array('weather_id' => intval($this->getId())));
		if ($next != null) {
			if (!is_object($cron)) {
				$cron = new cron();
				$cron->setClass(__CLASS__);
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
				$forcast_template = getTemplate('core', $version, 'forecastIMG', __CLASS__);
			} else {
				$forcast_template = getTemplate('core', $version, 'forecast', __CLASS__);
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
			return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'currentIMG', __CLASS__)));
		} else {
			return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'current', __CLASS__)));
		}
	}

	public function updateWeatherData() {
        if ($this->getIsEnable() == 0){
          	return;
        }
		if (trim($this->getConfiguration('lat')) == '' || trim($this->getConfiguration('long')) == '') {
			throw new Exception(__('La latitude et la longitude ne peut être vide', __FILE__));
		}
		$url = config::byKey('service::cloud::url') . '/service/weatherapi';
		$url .= '?lat=' . str_replace(',','.',trim($this->getConfiguration('lat')));
		$url .= '&long=' . str_replace(',','.',trim($this->getConfiguration('long')));
		$url .= '&lang=' . substr(config::byKey('language'), 0, 2);
		$request_http = new com_http($url);
		$request_http->setHeader(array('Autorization: ' . sha512(mb_strtolower(config::byKey('market::username')) . ':' . config::byKey('market::password'))));
		$datas = json_decode($request_http->exec(10), true);
		log::add(__CLASS__, 'debug', $url . ' : ' . json_encode($datas));
		if ($datas['state'] != 'ok') {
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

		if(isset($datas['data']['today']['temperature']['min'])){
			$changed = $this->checkAndUpdateCmd('temperature_min', $datas['data']['today']['temperature']['min']) || $changed;
			$changed = $this->checkAndUpdateCmd('temperature_max', $datas['data']['today']['temperature']['max']) || $changed;
		}
		
		if(isset($datas['data']['today']['air_quality']['co'])){
			$changed = $this->checkAndUpdateCmd('air_quality_co', $datas['data']['today']['air_quality']['co']) || $changed;
			$changed = $this->checkAndUpdateCmd('air_quality_no2', $datas['data']['today']['air_quality']['no2']) || $changed;
			$changed = $this->checkAndUpdateCmd('air_quality_o3', $datas['data']['today']['air_quality']['o3']) || $changed;
			$changed = $this->checkAndUpdateCmd('air_quality_so2', $datas['data']['today']['air_quality']['so2']) || $changed;
			$changed = $this->checkAndUpdateCmd('air_quality_pm2.5', $datas['data']['today']['air_quality']['pm2_5']) || $changed;
			$changed = $this->checkAndUpdateCmd('air_quality_pm10', $datas['data']['today']['air_quality']['pm10']) || $changed;
		}

		$this->checkAndUpdateCmd('visibility', $datas['data']['today']['visibility']['value']);
		$this->checkAndUpdateCmd('uv', $datas['data']['today']['uv']['value']);

		if(isset($datas['data']['today']['sun'])) {
			$cmd = $this->getCmd('info', 'sunrise');
		  if (is_object($cmd) && $cmd->execCmd() != date('Gi', strtotime($datas['data']['today']['sun']['rise']))) {
			  $cmd->setCache('value', date('Gi', strtotime($datas['data']['today']['sun']['rise'])));
			  $cmd->setCache('collectDate', date('Y-m-d H:i:s'));
		  }

		  $cmd = $this->getCmd('info', 'sunset');
		  if (is_object($cmd) && $cmd->execCmd() != date('Gi', strtotime($datas['data']['today']['sun']['set']))) {
			  $cmd->setCache('value', date('Gi', strtotime($datas['data']['today']['sun']['set'])));
			  $cmd->setCache('collectDate', date('Y-m-d H:i:s'));
		  }
		}

		for ($i = 1; $i < 4; $i++) {
			$changed = $this->checkAndUpdateCmd('temperature_h' . $i, $datas['data']['hour +' . $i]['temperature']['value']) || $changed;
			$changed = $this->checkAndUpdateCmd('condition_h' . $i, $datas['data']['hour +' . $i]['description']) || $changed;
			$changed = $this->checkAndUpdateCmd('condition_id_h' . $i, $datas['data']['hour +' . $i]['summary_id']) || $changed;
			if (isset($datas['data']['hour +' . $i]['rain']['value'])) {
				$changed = $this->checkAndUpdateCmd('rain_h' . $i, $datas['data']['hour +' . $i]['rain']['value']) || $changed;
			}
			if (isset($datas['data']['hour +' . $i]['snow']['value'])) {
				$changed = $this->checkAndUpdateCmd('snow_h' . $i, $datas['data']['hour +' . $i]['snow']['value']) || $changed;
			}
			$this->checkAndUpdateCmd('uv_h' . $i, $datas['data']['hour +' . $i]['uv']['value']);
			$this->checkAndUpdateCmd('wind_speed_h' . $i, $datas['data']['hour +' . $i]['wind']['speed']);
			if(isset($datas['data']['hour +' . $i]['wind']['deg'])){
				$this->checkAndUpdateCmd('wind_direction_h' . $i, $datas['data']['hour +' . $i]['wind']['deg']);
			}
			$this->checkAndUpdateCmd('visibility_h' . $i, $datas['data']['hour +' . $i]['visibility']['value']);
			$this->checkAndUpdateCmd('chance_rain_h' . $i, $datas['data']['hour +' . $i]['rain']['chance']);
			$this->checkAndUpdateCmd('chance_snow_h' . $i, $datas['data']['hour +' . $i]['snow']['chance']);
			$this->checkAndUpdateCmd('humidity_h' . $i, $datas['data']['hour +' . $i]['humidity']['value']);
		}

		for ($i = 1; $i < 7; $i++) {
			$changed = $this->checkAndUpdateCmd('temperature_' . $i . '_min', $datas['data']['day +' . $i]['temperature']['min']) || $changed;
			$changed = $this->checkAndUpdateCmd('temperature_' . $i . '_max', $datas['data']['day +' . $i]['temperature']['max']) || $changed;
			$changed = $this->checkAndUpdateCmd('condition_' . $i, $datas['data']['day +' . $i]['description']) || $changed;
			$changed = $this->checkAndUpdateCmd('condition_id_' . $i, $datas['data']['day +' . $i]['summary_id']) || $changed;
			if (isset($datas['data']['day +' . $i]['rain']['value'])) {
				$changed = $this->checkAndUpdateCmd('rain_' . $i, $datas['data']['day +' . $i]['rain']['value']) || $changed;
			}
			$this->checkAndUpdateCmd('temperature_' . $i, $datas['data']['day +' . $i]['temperature']['value']);
			$this->checkAndUpdateCmd('uv_' . $i, $datas['data']['day +' . $i]['uv']['value']);
			$this->checkAndUpdateCmd('wind_speed_' . $i, $datas['data']['day +' . $i]['wind']['speed']);
			if(isset($datas['data']['day +' . $i]['wind']['deg'])){
				$this->checkAndUpdateCmd('wind_direction_' . $i, $datas['data']['day +' . $i]['wind']['deg']);
			}

			if ($i < 4 && isset($datas['data']['day +' . $i]['air_quality']['co'])) {
				$changed = $this->checkAndUpdateCmd('air_quality_co_' . $i, $datas['data']['day +' . $i]['air_quality']['co']) || $changed;
				$changed = $this->checkAndUpdateCmd('air_quality_no2_' . $i, $datas['data']['day +' . $i]['air_quality']['no2']) || $changed;
				$changed = $this->checkAndUpdateCmd('air_quality_o3_' . $i, $datas['data']['day +' . $i]['air_quality']['o3']) || $changed;
				$changed = $this->checkAndUpdateCmd('air_quality_so2_' . $i, $datas['data']['day +' . $i]['air_quality']['so2']) || $changed;
				$changed = $this->checkAndUpdateCmd('air_quality_pm2.5_' . $i, $datas['data']['day +' . $i]['air_quality']['pm2_5']) || $changed;
				$changed = $this->checkAndUpdateCmd('air_quality_pm10_' . $i, $datas['data']['day +' . $i]['air_quality']['pm10']) || $changed;
			}
		}
		if ($changed) {
			$this->refreshWidget();
		}
	}
}

class weatherCmd extends cmd {

	public static $_widgetPossibility = array('custom' => false);

	public function execute($_options = array()) {
		if ($this->getLogicalId() == 'refresh') {
			$this->getEqLogic()->updateWeatherData();
		}
		return false;
	}
}
