<?php
/**
 * PRedisCacheHttpSession class
 *
 * @author Lincoln Sanderson <lincoln@paperton.com>
 */

class PRedisCacheHttpSession extends CCacheHttpSession
{
	/**
	 * Prefix to the keys for storing cached data
	 */
	const CACHE_KEY_PREFIX='Yii.PRedisCacheHttpSession.';

	/**
	 * Cache DB for Redis to select before all operations. This should be unique from your existing Redis Cache DB, which is default 0.
	 */
	public $database=9;

	private $_cache;

	/**
	 * Initializes the application component.
	 * This method creates the second instance of Redis, to enable us to write cache stuff and perform commands without affecting
	 * live server cache on other things.
	 */
	public function init()
	{
		try {
			if(!class_exists('CRedisCache', false))
				throw new CException('Please ensure that CRedisCache is installed and instantiatable.');
		} catch(Exception $e){
			throw new CException($e->getMessage());
		}

		$cacheClass = get_class(Yii::app()->getComponent($this->cacheID));
		//Cache has already been instanciated at this point, so go ahead and create an instance of it straight away.
		$this->_cache=new $cacheClass;

		if(!($this->_cache instanceof ICache))
			throw new CException(Yii::t('yii','PRedisCacheHttpSession.cacheID is invalid. Please make sure "{id}" refers to a valid cache application component.',
				array('{id}'=>$this->cacheID)));

		//Pull the existing redis cache servers config and init
		$this->_cache->servers = Yii::app()->getComponent($this->cacheID)->servers;
		$this->_cache->init();

		//This is basically the most important line of code - select the unique db on our privately instanciated Cache.
		$this->_cache->select($this->database);

		//Carry on as you were...
		parent::init();
	}

	public function readSession($id)
	{
		$this->_cache->select($this->database);
		$data=$this->_cache->get($this->calculateKey($id));
		return $data===false?'':$data;
	}

	public function writeSession($id,$data)
	{
		$this->_cache->select($this->database);
		return $this->_cache->set($this->calculateKey($id),$data,$this->getTimeout());
	}

	public function destroySession($id)
	{
		$this->_cache->select($this->database);
	    return $this->_cache->delete($this->calculateKey($id));
	}

	protected function calculateKey($id)
	{
	    return self::CACHE_KEY_PREFIX.$id;
	}
}
