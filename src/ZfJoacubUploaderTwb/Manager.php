<?php

namespace ZfJoacubUploaderTwb;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;
use Zend\Json\Expr;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Controller\Plugin\Forward;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Http\Request;
use Zend\EventManager\StaticEventManager;
use Zend\Mvc\Router\RouteInterface;
use Zend\Mvc\Router\RoutePluginManager;
use Nette\Diagnostics\Debugger;

class Manager
{
    /**
     * @var ServiceLocatorInterface 
     */
    protected $sl;

    /**
     * @var array
     */
    protected $cache;
    
    /**
     * @var $uploaderId string
     */
    protected $uploaderId = 'fileupload';
    
    /**
     * @var $renderer \Zend\View\Renderer
     */
    protected $renderer;
    
    /**
     * 
     * @var $options array
     */
    protected $options = array();
    
    protected $instances = array();
    
    protected static $templatesJquery = array();
    
    protected  static $templatesModalGallery = array();
    
    protected $defaultOptions = array();
    
    protected $keywords = array();
    
    /**
     * Set the Module specific configuration parameters
     * 
     * @param Array $params
     */
    public function __construct(ServiceLocatorInterface $sl) {
        $this->sl = $sl;
        $this->setRenderer();
        $this->renderScripts();
        
        $app = $this->sl->get('application');
		$configOptions = $this->sl->get('configuration');
		$this->setDefaultOptions($configOptions['JoacubUploader']['options']);
		$sm = $app->getServiceManager();
		$em = $app->getEventManager();
		
        $em->attach(MvcEvent::EVENT_RENDER, array($this, 'attachUploader'));
        
        $em->attach(MvcEvent::EVENT_FINISH, array($this, 'onFinish'));
        
        $em->attach(MvcEvent::EVENT_RENDER, array($this, 'renderFinish'));
    }
    
    public function getInstance($instance = null)
    {
        if($instance === null)
            return clone $this;
        return $this->instances[$instance];
    }
    
    public function setInstance($instance)
    {
        $this->instances[$instance->getUploaderId()] = $instance;
    }
    
    public function create($uploader)
    {
        $instance = $this->getInstance();
        
        $instance->setUploaderId($uploader);
        
        $config = $this->sl->get('Configuration');
        if(isset($config['JoacubUploader']['uploads'][$instance->getUploaderId()]) && is_array($config['JoacubUploader']['uploads'][$instance->getUploaderId()])) {
            $instance->setOptions($config['JoacubUploader']['uploads'][$instance->getUploaderId()]);
        }
        
        $instance->mergeOptions();
        
        $keywrodsStrategy = $instance->getOption('keywords', array());
        
        if($keywrodsStrategy instanceof \Closure) {
        	$instance->setKeywords($keywrodsStrategy($this->sl, $instance));
        } else {
        	$instance->setKeywords($keywrodsStrategy);
        }
        
        $instance->addTemplates();
        
        $this->setInstance($instance);
        
        return $instance;
    }
    
    public function setKeywords(Array $keywords = array())
    {
    	$this->keywords = $keywords;
    	return $this;
    }
    
    public function getKeywords()
    {
    	return $this->keywords;
    }
    
    public function setRenderer()
    {
        $this->renderer = $this->sl->get('ViewRenderer');
    }
    
    /**
     * @return \Zend\View\Renderer
     */
    public function getRenderer()
    {
        return $this->renderer;
    }
    
    public function addTemplates()
    {
        $uploadTemplateId = preg_replace('/[^a-z0-9]+/i', '-', $this->getOption('uploadTemplatePhtml'));
        self::$templatesJquery[$this->getOption('uploadTemplatePhtml')] = $uploadTemplateId;
        $this->setOption('uploadTemplateId', $uploadTemplateId);
        
        $downloadTemplateId = preg_replace('/[^a-z0-9]+/i', '-', $this->getOption('downloadTemplatePhtml'));
        self::$templatesJquery[$this->getOption('downloadTemplatePhtml')] = $downloadTemplateId;
        $this->setOption('downloadTemplateId', $downloadTemplateId);
        
        $modalGalleryTemplateId = preg_replace('/[^a-z0-9]+/i', '-', $this->getOption('modalGalleryTemplatePhtml'));
        self::$templatesModalGallery[$this->getOption('modalGalleryTemplatePhtml')] = $modalGalleryTemplateId;
        $this->setOption('modalGalleryTemplateId', $modalGalleryTemplateId);
        
        return $this;
    }
    
    public function getTemplatesJquery()
    {
        return self::$templatesJquery;
    }
    
    public function getTemplatesModalGallery()
    {
        return self::$templatesModalGallery;
    }
    
    public function __toString()
    {
        $renderer = $this->getRenderer();
        
        $options = Json::encode($this->getOptions(), false, array('enableJsonExprFinder' => true));
        $renderer->getEngine()->inlineScript()->appendScript('optionsZfJoacubUploaderTwb["' . $this->getUploaderId() . '"] = ' . $options . '; ');
        
        $instance = $this;
        $viewModel = new ViewModel(array('uploader' => $instance));
        
        $viewModel->setTemplate($this->getOption('uploaderTemplate'));
        $html = $renderer->render($viewModel);
        
        //limpiamos las opcioens para el siguiente
        return $html;
    }
    
    public function renderScripts()
    {
        
        return $this;
    }
    
    public function setUploaderId($id)
    {
        $this->uploaderId = $id;
        return $this;
    }
    
    public function getUploaderId()
    {
        return $this->uploaderId;
    }
    
    public function setOptions(array $options)
    {
        $this->options = $options;
        return $this;
    }
    
    public function getOptions()
    {
        return $this->options;
    }
    
    /**
     * 
     * @param array $options
     * @return \ZfJoacubUploaderTwb\Manager
     */
    public function setDefaultOptions(Array $options)
    {
        $this->defaultOptions = $options;
        return $this;
    }
    
    /**
     * 
     * @return multitype:
     */
    public function getDefaultOptions()
    {
        return $this->defaultOptions;
    }
    
    /**
     * 
     * @param string $option
     * @param string $value
     */
    public function setOption($option, $value)
    {
        $this->options[$option] = $value;
        return $this;
    }
    
    /**
     * 
     * @param string $option
     * @param string $default
     * @return \ZfJoacubUploaderTwb\$options
     */
    public function getOption($option, $default = null)
    {
        if(!isset($this->options[$option]))
            $this->options[$option] = $default;
        return $this->options[$option];
    }

    protected function mergeOptions()
    {
        $request = $this->sl->get('application')->getRequest();
        $request instanceof Request;
        
        $options = $this->getOptions();
        
        $this->setOptions(
            $options +
                 array(
                    'url' => $request->getUri()
                        ->toString() . '?uploader=' . $this->getUploaderId()
                ) + $this->getDefaultOptions());
        
        return $this;
    }
    
    /**
     * 
     * @param unknown $uploader
     */
    public function setUploader($uploader)
    {
        $this->uploader = $uploader;
        return $this;
    }
    
    public function getUploader()
    {
        return $this->uploader;
    }
    
    public function renderFinish()
    {
        $renderer = $this->getRenderer();
        
        $inlineScript = $renderer->getEngine()->inlineScript()->setAllowArbitraryAttributes(true);
        
        foreach($this->instances as $instance) {
        	$templatesJquery = $instance->getTemplatesJquery();
        	$uploaderId = $instance->getUploaderId();
        	foreach($templatesJquery as $template => $id) {
        		$viewModel = new ViewModel();
        		$viewModel->setVariable('idGallery', '#'.$uploaderId . '-gallery');
        		$viewModel->setTemplate($template);
        		$html = $renderer->render($viewModel);
        		$inlineScript->appendScript($html, 'text/x-tmpl', array('id' => $id, 'noescape' => true));
        	}
        }
        
        
        $renderer->getEngine()->inlineScript()
        ->prependScript('var optionsZfJoacubUploaderTwb = new Array();')
        ->appendFile('//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js')
        ->appendFile($renderer->getEngine()->basePath() . '/jQuery-File-Upload/js/vendor/jquery.ui.widget.js')
        ->appendFile($renderer->getEngine()->basePath() . '/jQuery-File-Upload/JavaScript-Templates/js/tmpl.min.js')
        ->appendFile($renderer->getEngine()->basePath() . '/jQuery-File-Upload/JavaScript-Load-Image/js/load-image.min.js')
        ->appendFile($renderer->getEngine()->basePath() . '/jQuery-File-Upload/JavaScript-Canvas-to-Blob/js/canvas-to-blob.min.js')
        ->appendFile($renderer->getEngine()->basePath() . '/jQuery-File-Upload/Gallery/js/jquery.blueimp-gallery.min.js')
        ->appendFile($renderer->getEngine()->basePath() . '/jQuery-File-Upload/js/jquery.iframe-transport.js')
        ->appendFile($renderer->getEngine()->basePath() . '/jQuery-File-Upload/js/jquery.fileupload.js')
        ->appendFile($renderer->getEngine()->basePath() . '/jQuery-File-Upload/js/jquery.fileupload-process.js')
        ->appendFile($renderer->getEngine()->basePath() . '/jQuery-File-Upload/js/jquery.fileupload-image.js')
        ->appendFile($renderer->getEngine()->basePath() . '/jQuery-File-Upload/js/jquery.fileupload-audio.js')
        ->appendFile($renderer->getEngine()->basePath() . '/jQuery-File-Upload/js/jquery.fileupload-video.js')
        ->appendFile($renderer->getEngine()->basePath() . '/jQuery-File-Upload/js/jquery.fileupload-validate.js')
        ->appendFile($renderer->getEngine()->basePath() . '/jQuery-File-Upload/js/jquery.fileupload-jquery-ui.js')
        ->appendFile($renderer->getEngine()->basePath() . '/jQuery-File-Upload/js/jquery.fileupload-ui.js')
        ->appendFile($renderer->getEngine()->basePath() . '/jQuery-File-Upload/js/main.js')
        ->appendFile($renderer->getEngine()->basePath() . '/jQuery-File-Upload/js/cors/jquery.xdr-transport.js', 'text/javascript', array('conditional' => 'gte IE 8'));
        
    }
    
    public function attachUploader(\Zend\Mvc\MvcEvent  $e)
    {
        $request = $e->getApplication()->getRequest();
        $request instanceof Request;
        if($request->isXmlHttpRequest()) {
            $match = $e->getRouteMatch();
            $controller = $match->getParam('controller');
            
            $locator = $e->getApplication()->getServiceManager()->get('ControllerLoader');
            $controllerClass = $locator->get($controller);
            
            $controllerClass->forward()->dispatch('ZfJoacubUploaderTwb\\Controller\\Uploader', array('action' => 'index'));
            exit;
        }
        
    }
    
    public function onFinish($event)
    {
        $renderer = $this->getRenderer();
        
        foreach($this->instances as $instance) {
	        $templatesModalGallery = $instance->getTemplatesModalGallery();
	        
	        $uploaderId = $instance->getUploaderId();
	        
	        $html = '';
	        foreach($templatesModalGallery as $template => $id) {
	        	$viewModel = new ViewModel();
	        	$viewModel->setVariable('id', $uploaderId . '-gallery');
	            $viewModel->setTemplate($template);
	            $html .= $renderer->render($viewModel);
	        }
        }
        
        $response    = $event->getApplication()->getResponse();
        $injected    = preg_replace('/<\/body>/i', $html . "\n</body>", $response->getBody(), 1);
        
        $response->setContent($injected);
    }
    
}
