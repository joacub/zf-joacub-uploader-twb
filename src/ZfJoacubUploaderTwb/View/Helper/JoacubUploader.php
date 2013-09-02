<?php
namespace ZfJoacubUploaderTwb\View\Helper;
use Zend\View\Helper\AbstractHelper;
use ZfJoacubUploaderTwb\Manager;

class ZfJoacubUploaderTwb extends AbstractHelper
{

	/**
	 *
	 * @var Manager Service
	 */
	protected $service;

	/**
	 *
	 * @var array $params
	 */
	protected $event;

	/**
	 * Called upon invoke
	 *
	 * @param integer $id        	
	 * @return FileBank\Entity\File
	 */
	public function __invoke ($uploader)
	{
		$this->getService()->attachUploader($this->getEvent());
		$this->getService()->renderFinish();
		return $this->getService()->create($uploader);
	}

	/**
	 * Get FileBank service.
	 *
	 * @return Manager
	 */
	public function getService ()
	{
		return $this->service;
	}

	/**
	 * Set FileBank service.
	 *
	 * @param
	 *        	$service
	 */
	public function setService ($service)
	{
		$this->service = $service;
		return $this;
	}
	
	public function setEvent($event)
	{
		$this->event = $event;
		return $this;
	}
	
	public function getEvent()
	{
		return $this->event;
	}
}