<?php
namespace Classes\Common;
/**
 *
 * @class Users
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class Settings
{
    private $log;
    private $code;
    private $value;
    private $settingExists;

    public function __construct($code)
    {
        require_once($_SERVER['DOCUMENT_ROOT'] . '/docker-config.php');
        require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Db.php');
        require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php';
        
        $this->log = new \Classes\Common\Log('classes - Common - Settings.log');
        $dbClass = new \Classes\Common\Db();
        $sql = 'select * from settings where code = "' . $code . '"';
        $settings = $dbClass->execQueryArray($sql);
        $this->log->write(__LINE__ . ' __construct.settings - ' . json_encode($settings, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        if (isset($settings[0])) {
            $this->code = $settings[0]['code'];
            $this->value = $settings[0]['value'];
            $this->settingExists = TRUE;
        }
        else
        {
            $this->code = '';
            $this->value = $code;
            $this->settingExists = FALSE;
        }
        //return $this->settingExists;
    }
    
    public function getValue()
    {
        return $this->value;
    }
    
    public function setValue($value)
    {
        if ($this->settingExists) {
            $db = new Db();
            $sql = 'update settings set value = "' . $value . '" where code = "' . $this->code . '"';
            if ($db->execQuery($sql)) {
                $this->value = $value;
            }
        }
        else
        {
            $db = new Db();
            $sql = 'insert into settings (code, value) values ("' . $this->code . '", "' . $value . '")';
            if ($db->execQuery($sql)) {
                $this->value = $value;
            }
        }
    }
    
    public function isSettingExists()
    {
        return $this->settingExists;
    }
}

?>