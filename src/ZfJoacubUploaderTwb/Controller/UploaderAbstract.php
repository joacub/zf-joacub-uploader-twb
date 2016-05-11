<?php

namespace ZfJoacubUploaderTwb\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use FileBank\Entity\File;
use Nette\Diagnostics\Debugger;
/*
 * jQuery File Upload Plugin PHP Class 6.1
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */

abstract class UploaderAbstract extends AbstractActionController
{
    protected $options;
    // PHP File Upload error message codes:
    // http://php.net/manual/en/features.file-upload.errors.php
    protected $error_messages = array(
        1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        3 => 'The uploaded file was only partially uploaded',
        4 => 'No file was uploaded',
        6 => 'Missing a temporary folder',
        7 => 'Failed to write file to disk',
        8 => 'A PHP extension stopped the file upload',
        'post_max_size' => 'The uploaded file exceeds the post_max_size directive in php.ini',
        'max_file_size' => 'File is too big',
        'min_file_size' => 'File is too small',
        'accept_file_types' => 'Filetype not allowed',
        'max_number_of_files' => 'Maximum number of files exceeded',
        'max_width' => 'Image exceeds maximum width',
        'min_width' => 'Image requires a minimum width',
        'max_height' => 'Image exceeds maximum height',
        'min_height' => 'Image requires a minimum height',
        'abort' => 'abortando'
    );
    
    /**
     * 
     * @var \FileBank\Manager
     */
    protected $fileBank;
    
    /**
     * 
     * @var \ZfJoacubUploaderTwb\Manager
     */
    protected $uploader;

    function indexAction($initialize = true) {
        
        $uploader = $this->getRequest()->getQuery('uploader');
        
        if(empty($uploader))
        	throw new \Exception('No has definido un uploader');
        
        $this->uploader = $this->getServiceLocator()->get('ZfJoacubUploaderTwb')->getInstance($uploader);
        $this->fileBank = $this->getServiceLocator()->get('FileBank');
        
        $options = $this->uploader->getOptions();
        $this->options = $options;
        
        if ($options) {
            $this->options = array_merge($this->options, $options);
        }
        
        if ($initialize) {
            $this->initialize();
        }
        
        return $this->response;
    }

    protected function initialize() {
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'OPTIONS':
            case 'HEAD':
                $this->head();
                break;
            case 'GET':
                $this->get();
                break;
            case 'PATCH':
            case 'PUT':
            case 'POST':
                $this->post();
                break;
            case 'DELETE':
                $this->delete();
                break;
            default:
                $this->header('HTTP/1.1 405 Method Not Allowed');
        }
    }

    protected function get_full_url() {
        return $this->getServiceLocator()->get('viewrenderer')->getEngine()->basePath();
    }

    protected function get_user_id() {
        @session_start();
        return session_id();
    }

    protected function get_download_url(\FileBank\Entity\File $file_name, $version = null) {
        if ($this->options['download_via_php']) {
            return $file_name->getDownloadUrl();
        }
        return $file_name->getUrl();
    }

    protected function set_file_delete_properties($file, \FileBank\Entity\File $fileName) {

        $uriParts = explode('?', $_SERVER["REQUEST_URI"]);

        if(count($uriParts) > 1) {
            $file->deleteUrl = '?' . $uriParts[1] . '&uploader='. $this->uploader->getUploaderId() .'&file='. $fileName->getId();
        } else {
            $file->deleteUrl = '?uploader='. $this->uploader->getUploaderId() .'&file='. $fileName->getId();
        }

        
        $file->deleteType = $this->options['delete_type'];
        if ($file->deleteType !== 'DELETE') {
            $file->deleteUrl .= '&_method=DELETE';
        }
        if ($this->options['access_control_allow_credentials']) {
            $file->deleteWithCredentials = true;
        }
        
    }

    // Fix for overflowing signed 32 bit integers,
    // works for sizes up to 2^32-1 bytes (4 GiB - 1):
    protected function fix_integer_overflow($size) {
        if ($size < 0) {
            $size += 2.0 * (PHP_INT_MAX + 1);
        }
        return $size;
    }

    /**
     * 
     * @param unknown $file_path
     * @param string $clear_stat_cache
     * @return number
     */
    protected function get_file_size($file_path, $clear_stat_cache = false) {
        if ($clear_stat_cache) {
            clearstatcache(true, $file_path);
        }
        return $this->fix_integer_overflow(filesize($file_path));

    }

    /**
     * 
     * @param \FileBank\Entity\File $file_name
     * @return boolean
     */
    protected function is_valid_file_object(\FileBank\Entity\File $file_name) {
        $file_path = $file_name->getAbsolutePath();
        if (is_file($file_path)) {
            return true;
        }
        return false;
    }

    protected function get_file_object(\FileBank\Entity\File $file_name) {
        if ($this->is_valid_file_object($file_name)) {
            $file = new \stdClass();
            $file->name = $file_name->getName();
            $file->size = $file_name->getSize();
            $file->url = $this->get_download_url($file_name);
            foreach($this->options['image_versions'] as $version => $options) {
                $this->fileBank->disableCreateInAjax();
                $versionFile = $this->fileBank->getVersion($file_name, $options);
                if (!empty($versionFile)) {
                        $file->{$version.'Url'} = $this->get_download_url(
                            $versionFile,
                            $version
                        );
                }
            }
            
            $this->set_file_delete_properties($file, $file_name);
            return $file;
        }
        return null;
    }

    /**
     * 
     * @param string $iteration_method
     * @return multitype:
     */
    protected function get_file_objects($iteration_method = 'get_file_object') {
        $files = $this->fileBank->getFilesByKeywords($this->uploader->getKeywords(), true);
        return array_values(array_filter(array_map(
            array($this, $iteration_method),
            $files
        )));
    }

    /**
     * 
     * @return number
     */
    protected function count_file_objects() {
        return count($this->get_file_objects('is_valid_file_object'));
    }


    /**
     * 
     * @param unknown $error
     * @return Ambigous <unknown, multitype:string >
     */
    protected function get_error_message($error) {
        return array_key_exists($error, $this->error_messages) ?
            $this->error_messages[$error] : $error;
    }

    /**
     * 
     * @param unknown $val
     * @return number
     */
    function get_config_bytes($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        switch($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }
        return $this->fix_integer_overflow($val);
    }

    /**
     * 
     * @param unknown $uploaded_file
     * @param unknown $file
     * @param unknown $error
     * @param unknown $index
     * @return boolean
     */
    protected function validate($uploaded_file, $file, $error, $index) {
        if ($error) {
            $file->error = $this->get_error_message($error);
            return false;
        }
        $content_length = $this->fix_integer_overflow(intval($_SERVER['CONTENT_LENGTH']));
        if ($content_length > $this->get_config_bytes(ini_get('post_max_size'))) {
            $file->error = $this->get_error_message('post_max_size');
            return false;
        }
        if (!preg_match($this->options['accept_file_types'], $file->name)) {
            $file->error = $this->get_error_message('accept_file_types');
            return false;
        }
        if ($uploaded_file && is_uploaded_file($uploaded_file)) {
            $file_size = $this->get_file_size($uploaded_file);
        } else {
            $file_size = $content_length;
        }
        if ($this->options['max_file_size'] && (
                $file_size > $this->options['max_file_size'] ||
                $file->size > $this->options['max_file_size'])
            ) {
            $file->error = $this->get_error_message('max_file_size');
            return false;
        }
        if ($this->options['min_file_size'] &&
            $file_size < $this->options['min_file_size']) {
            $file->error = $this->get_error_message('min_file_size');
            return false;
        }
        if (is_int($this->options['max_number_of_files']) && (
                $this->count_file_objects() >= $this->options['max_number_of_files'])
            ) {
            $file->error = $this->get_error_message('max_number_of_files');
            return false;
        }
        list($img_width, $img_height) = @getimagesize($uploaded_file);
        if (is_int($img_width)) {
            if ($this->options['max_width'] && $img_width > $this->options['max_width']) {
                $file->error = $this->get_error_message('max_width');
                return false;
            }
            if ($this->options['max_height'] && $img_height > $this->options['max_height']) {
                $file->error = $this->get_error_message('max_height');
                return false;
            }
            if ($this->options['min_width'] && $img_width < $this->options['min_width']) {
                $file->error = $this->get_error_message('min_width');
                return false;
            }
            if ($this->options['min_height'] && $img_height < $this->options['min_height']) {
                $file->error = $this->get_error_message('min_height');
                return false;
            }
        }
        return true;
    }

    protected function upcount_name_callback($matches) {
        $index = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
        $ext = isset($matches[2]) ? $matches[2] : '';
        return ' ('.$index.')'.$ext;
    }

    protected function upcount_name($name) {
        return preg_replace_callback(
            '/(?:(?: \(([\d]+)\))?(\.[^.]+))?$/',
            array($this, 'upcount_name_callback'),
            $name,
            1
        );
    }

    protected function handle_form_data($file, $index) {
        // Handle form data, e.g. $_REQUEST['description'][$index]
    }

    /**
     * 
     * @param unknown $file_path
     * @return boolean
     */
    protected function orient_image($file_path) {
        if (!function_exists('exif_read_data')) {
            return false;
        }
        $exif = @exif_read_data($file_path);
        if ($exif === false) {
            return false;
        }
        $orientation = intval(@$exif['Orientation']);
        if (!in_array($orientation, array(3, 6, 8))) {
            return false;
        }
        $image = @imagecreatefromjpeg($file_path);
        switch ($orientation) {
            case 3:
                $image = @imagerotate($image, 180, 0);
                break;
            case 6:
                $image = @imagerotate($image, 270, 0);
                break;
            case 8:
                $image = @imagerotate($image, 90, 0);
                break;
            default:
                return false;
        }
        $success = imagejpeg($image, $file_path);
        // Free up memory (imagedestroy does not delete files):
        @imagedestroy($image);
        return $success;
    }

    /**
     * 
     * @param unknown $uploaded_file
     * @param unknown $name
     * @param unknown $size
     * @param unknown $type
     * @param unknown $error
     * @param string $index
     * @param string $content_range
     * @return \stdClass
     */
    protected function handle_file_upload($uploaded_file, $name, $size, $type, $error,
            $index = null, $content_range = null) {
        $file = new \stdClass();
        $file->name = $name;
        $file->size = $this->fix_integer_overflow(intval($size));
        $file->type = $type;
        if ($this->validate($uploaded_file, $file, $error, $index)) {
            $this->handle_form_data($file, $index);
            
            if ($uploaded_file && is_uploaded_file($uploaded_file)) {
                $fileBankFile = $this->fileBank->save(array($uploaded_file, $name), $this->uploader->getKeywords());
                $fileBankFile instanceof File;
            } 
            
            $file_size = $fileBankFile->getSize(); 
            if ($this->options['orient_image']) {
                $this->orient_image($fileBankFile->getAbsolutePath());
            }
            $file->url = $this->get_download_url($fileBankFile);
            foreach($this->options['image_versions'] as $version => $options) {
                $this->fileBank->disableCreateInAjax();
                try {
                    $versionFile = $this->fileBank->getVersion($fileBankFile, $options, array());
                    $this->fileBank->createFileVersion($versionFile);
                    if (!empty($versionFile)) {
                        $file->{$version.'Url'} = $this->get_download_url(
                            $versionFile,
                            $version
                        );
                    }
                } catch(\Exception $e) {

                }

            }
            
            $file->size = $file_size;
            $this->set_file_delete_properties($file, $fileBankFile);
        }
        
        return $file;
    }

    protected function readfile($file_path) {
        return readfile($file_path);
    }

    protected function body($str) {
        echo $str;
    }
    
    protected function header($str) {
        header($str);
    }

    protected function generate_response($content, $print_response = true) {
        if ($print_response) {
            $json = json_encode($content);
            $redirect = isset($_REQUEST['redirect']) ?
                stripslashes($_REQUEST['redirect']) : null;
            if ($redirect) {
                $this->header('Location: '.sprintf($redirect, rawurlencode($json)));
                return;
            }
            $this->head();
            if (isset($_SERVER['HTTP_CONTENT_RANGE'])) {
                $files = isset($content[$this->options['param_name']]) ?
                    $content[$this->options['param_name']] : null;
                if ($files && is_array($files) && is_object($files[0]) && $files[0]->size) {
                    $this->header('Range: 0-'.($this->fix_integer_overflow(intval($files[0]->size)) - 1));
                }
            }
            
            $this->body($json);
        }
        return $content;
    }

    protected function get_version_param() {
        return isset($_GET['version']) ? basename(stripslashes($_GET['version'])) : null;
    }

    protected function get_file_type($file_path) {
        switch (strtolower(pathinfo($file_path, PATHINFO_EXTENSION))) {
            case 'jpeg':
            case 'jpg':
                return 'image/jpeg';
            case 'png':
                return 'image/png';
            case 'gif':
                return 'image/gif';
            default:
                return '';
        }
    }

    protected function download() {
        if (!$this->options['download_via_php']) {
            $this->header('HTTP/1.1 403 Forbidden');
            return;
        }
        $file_name = $this->get_file_name_param();
        if ($this->is_valid_file_object($file_name)) {
            $file_path = $this->get_upload_path($file_name, $this->get_version_param());
            if (is_file($file_path)) {
                if (!preg_match($this->options['inline_file_types'], $file_name)) {
                    $this->header('Content-Description: File Transfer');
                    $this->header('Content-Type: application/octet-stream');
                    $this->header('Content-Disposition: attachment; filename="'.$file_name.'"');
                    $this->header('Content-Transfer-Encoding: binary');
                } else {
                    // Prevent Internet Explorer from MIME-sniffing the content-type:
                    $this->header('X-Content-Type-Options: nosniff');
                    $this->header('Content-Type: '.$this->get_file_type($file_path));
                    $this->header('Content-Disposition: inline; filename="'.$file_name.'"');
                }
                $this->header('Content-Length: '.$this->get_file_size($file_path));
                $this->header('Last-Modified: '.gmdate('D, d M Y H:i:s T', filemtime($file_path)));
                $this->readfile($file_path);
            }
        }
    }

    protected function send_content_type_header() {
        $this->header('Vary: Accept');
        if (isset($_SERVER['HTTP_ACCEPT']) &&
            (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
            $this->header('Content-type: application/json');
        } else {
            $this->header('Content-type: text/plain');
        }
    }

    protected function send_access_control_headers() {
        $this->header('Access-Control-Allow-Origin: '.$this->options['access_control_allow_origin']);
        $this->header('Access-Control-Allow-Credentials: '
            .($this->options['access_control_allow_credentials'] ? 'true' : 'false'));
        $this->header('Access-Control-Allow-Methods: '
            .implode(', ', $this->options['access_control_allow_methods']));
        $this->header('Access-Control-Allow-Headers: '
            .implode(', ', $this->options['access_control_allow_headers']));
    }

    public function head() {
        $this->header('Pragma: no-cache');
        $this->header('Cache-Control: no-store, no-cache, must-revalidate');
        $this->header('Content-Disposition: inline; filename="files.json"');
        // Prevent Internet Explorer from MIME-sniffing the content-type:
        $this->header('X-Content-Type-Options: nosniff');
        if ($this->options['access_control_allow_origin']) {
            $this->send_access_control_headers();
        }
        $this->send_content_type_header();
    }

    public function get($print_response = true) {
        if ($print_response && isset($_GET['download'])) {
            return $this->download();
        }
        $file_name = $this->getRequest()->getQuery('file');
        if ($file_name) {
            $response = array(
                substr($this->options['param_name'], 0, -1) => $this->get_file_object($file_name)
            );
        } else {
            $response = array(
                $this->options['param_name'] => $this->get_file_objects()
            );
        }
        return $this->generate_response($response, $print_response);
    }

    public function post($print_response = true) {
    	
        if (isset($_REQUEST['_method']) && $_REQUEST['_method'] === 'DELETE') {
            return $this->delete($print_response);
        }
        $upload = isset($_FILES[$this->options['param_name']]) ?
            $_FILES[$this->options['param_name']] : null;
        // Parse the Content-Disposition header, if available:
        $file_name = isset($_SERVER['HTTP_CONTENT_DISPOSITION']) ?
            rawurldecode(preg_replace(
                '/(^[^"]+")|("$)/',
                '',
                $_SERVER['HTTP_CONTENT_DISPOSITION']
            )) : null;
        // Parse the Content-Range header, which has the following form:
        // Content-Range: bytes 0-524287/2000000
        $content_range = isset($_SERVER['HTTP_CONTENT_RANGE']) ?
            preg_split('/[^0-9]+/', $_SERVER['HTTP_CONTENT_RANGE']) : null;
        $size =  $content_range ? $content_range[3] : null;
        $files = array();
        if ($upload && is_array($upload['tmp_name'])) {
            // param_name is an array identifier like "files[]",
            // $_FILES is a multi-dimensional array:
            foreach ($upload['tmp_name'] as $index => $value) {
                $files[] = $this->handle_file_upload(
                    $upload['tmp_name'][$index],
                    $file_name ? $file_name : $upload['name'][$index],
                    $size ? $size : $upload['size'][$index],
                    $upload['type'][$index],
                    $upload['error'][$index],
                    $index,
                    $content_range
                );
            }
        } else {
            // param_name is a single object identifier like "file",
            // $_FILES is a one-dimensional array:
            $files[] = $this->handle_file_upload(
                isset($upload['tmp_name']) ? $upload['tmp_name'] : null,
                $file_name ? $file_name : (isset($upload['name']) ?
                        $upload['name'] : null),
                $size ? $size : (isset($upload['size']) ?
                        $upload['size'] : $_SERVER['CONTENT_LENGTH']),
                isset($upload['type']) ?
                        $upload['type'] : $_SERVER['CONTENT_TYPE'],
                isset($upload['error']) ? $upload['error'] : null,
                null,
                $content_range
            );
        }
        
        return $this->generate_response(
            array($this->options['param_name'] => $files),
            $print_response
        );
    }

    public function delete($print_response = true) {
    	$message = 'Desconocido';
    	try {
        	$success = $this->fileBank->removeById($this->getRequest()->getQuery('file'));
    	} catch (\Exception $e) {
    		$success = false;
    		$message = $e->getMessage();
    	}
    	
        if (!$success) {
        	return $this->generate_response(array('success' => $success, 'error' => $message), $print_response);
        } else {
        	return $this->generate_response(array('success' => $success), $print_response);
        }
        
    }

}
