<?php
/**
 * Laravel 4 - Persistent Settings
 * 
 * @author   Andreas Lutro <anlutro@gmail.com>
 * @license  http://opensource.org/licenses/MIT
 * @package  l4-settings
 */

namespace anlutro\LaravelSettings;

use Illuminate\Support\Facades\Cache;
use \Illuminate\Support\Facades\Config;

abstract class SettingStore
{
	/**
	 * Cache key for save
	 */
	const CACHE_KEY = 'setting:cache';

	/**
	 * The settings data.
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * The settings updated data.
	 *
	 * @var array
	 */
	protected $updatedData = array();

	/**
	 * The settings updated data.
	 *
	 * @var array
	 */
	protected $persistedData = array();

	/**
	 * Whether the store has changed since it was last loaded.
	 *
	 * @var boolean
	 */
	protected $unsaved = false;

	/**
	 * Whether the settings data are loaded.
	 *
	 * @var boolean
	 */
	protected $loaded = false;

	/**
	 * Default values.
	 *
	 * @var array
	 */
	protected $defaults = [];

	/**
	 * Set default values.
	 *
	 * @param array $defaults
	 */
	public function setDefaults(array $defaults)
	{
		$this->defaults = $defaults;
	}

	/**
	 * Get a specific key from the settings data.
	 *
	 * @param  string|array $key
	 * @param  mixed        $default Optional default value.
	 *
	 * @return mixed
	 */
	public function get($key, $default = null)
	{
		if ($default === NULL && !is_array($key)) {
			$default = ArrayUtil::get($this->defaults, $key);
		}
        
		$this->load();

		return ArrayUtil::get($this->data, $key, $default);
	}

	/**
	 * Determine if a key exists in the settings data.
	 *
	 * @param  string  $key
	 *
	 * @return boolean
	 */
	public function has($key)
	{
		$this->load();

		return ArrayUtil::has($this->data, $key);
	}

	/**
	 * Set a specific key to a value in the settings data.
	 *
	 * @param string|array $key   Key string or associative array of key => value
	 * @param mixed        $value Optional only if the first argument is an array
	 */
	public function set($key, $value = null)
	{
		$this->load();
		$this->unsaved = true;
		
		if (is_array($key)) {
			foreach ($key as $k => $v) {
				ArrayUtil::set($this->data, $k, $v);
				ArrayUtil::set($this->updatedData, $k, $v);
			}
		} else {
			ArrayUtil::set($this->data, $key, $value);
			ArrayUtil::set($this->updatedData, $key, $value);
		}
	}

	/**
	 * Unset a key in the settings data.
	 *
	 * @param  string $key
	 */
	public function forget($key)
	{
		$this->unsaved = true;

		if ($this->has($key)) {
			ArrayUtil::forget($this->data, $key);
			ArrayUtil::forget($this->updatedData, $key);
		}
	}

	/**
	 * Unset all keys in the settings data.
	 *
	 * @return void
	 */
	public function forgetAll()
	{
		$this->unsaved = true;
		$this->data = array();
		$this->updatedData = array();
	}

	/**
	 * Get all settings data.
	 *
	 * @return array
	 */
	public function all()
	{
		$this->load();

		return $this->data;
	}

	/**
	 * Save any changes done to the settings data.
	 *
	 * @return void
	 */
	public function save()
	{
		if (!$this->unsaved) {
			// either nothing has been changed, or data has not been loaded, so
			// do nothing by returning early
			return;
		}

		if (Config::get('settings.forgetCacheByWrite')) {
			Cache::forget(static::CACHE_KEY);
		}

		$this->write($this->data);
		$this->unsaved = false;
	}

	/**
	 * Make sure data is loaded.
	 *
	 * @param $force Force a reload of data. Default false.
	 */
	public function load($force = false)
	{
		if (!$this->loaded || $force) {
			$this->data = $this->readData();
            $this->persistedData = $this->data;
            $this->data = array_merge($this->updatedData, $this->data);
            $this->loaded = true;
		}
	}

	/**
	 * Read data from a store or cache
	 *
	 * @return array
	 */
	private function readData() {
		if (Config::get('settings.enableCache')) {
			return Cache::remember(static::CACHE_KEY, Config::get('settings.cacheTtl'), function () {
				return $this->read();
			});
		}
		return $this->read();
	}

	/**
	 * Read the data from the store.
	 *
	 * @return array
	 */
	abstract protected function read();

	/**
	 * Write the data into the store.
	 *
	 * @param  array  $data
	 *
	 * @return void
	 */
	abstract protected function write(array $data);
}
