<?php
/**
 *
 * @class Users
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class Settings
{
    public static function getSettingsValues($settings)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
		
		// Create connection
		$conn = Db::get_connection();
		// get values
		if (is_array ($settings))
			$values = '("' . implode ('","', $settings) . '")';
		else
			$values = '("' . $settings . '")';
		// exec sql
		$sql = 'select code, value from settings where code in ' . $values;
		$result = Db::exec_query_array($sql);
		
		if (!$result)
			return '';
		
		if (is_array ($settings)) 
		{
			$return = array();
			foreach ($settings as $setting)
			{
				$idKey = array_search ($setting, array_column ($result, 'code'));
				$return[] = array ($setting => $idKey !== false ? $result[$idKey]['value'] : "");
			}
		}
		else
		{
			$idKey = array_search ($settings, array_column ($result, 'code'));
			$return = $idKey !== false ? $result[$idKey]['value'] : "";
		}
		return $return;		
	}

    public static function setSettingsValues($values)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
		// Create connection
		$conn = Db::get_connection();
		// get values
		$settingValues = getSettingsValues (array_keys ($values));
		foreach ($settingValues as $value)
			if ($value['value'] == "")
				Db::exec_query('insert into settings (code, value) values ("' . $value['code'] . '", "' . $value['value'] . '")');
			else
				Db::exec_query('update settings set value = "' . $value['value'] . '" where code = "' . $value['code'] . '"');
		return 'ок';
	}
	
	
}

?>