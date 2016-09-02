<?php
/**
 * Created by PhpStorm.
 * User: johanrodriguezramos
 * Date: 2/9/16
 * Time: 16:17
 */

namespace ZfJoacubUploaderTwb\Controller;


class AbstractActionController extends \Zend\Mvc\Controller\AbstractActionController
{

    public function getServicelocator()
    {
        return $this->getEvent()->getApplication()->getServiceManager();
    }

}