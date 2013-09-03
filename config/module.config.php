<?php

namespace ZfJoacubUploaderTwb;

use Zend\Json\Expr;
return array(
    'router' => array(
        'routes' => array(
            // The following is a route to simplify getting started creating
            // new controllers and actions without needing to create a new
            // module. Simply drop new controllers in, and you can access them
            // using the path /application/:controller/:action
            'ZfJoacubUploaderTwb' => array(
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/uploader',
                    'defaults' => array(
                        'controller'    => 'ZfJoacubUploaderTwb\Controller\Uploader',
                        'action'        => 'index',
                    ),
                ),
            ),
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            __NAMESPACE__ . '\Controller\Uploader' => __NAMESPACE__ . '\Controller\UploaderController'
        ),
    ),
	'service_manager' => array(
		'aliases' => array(
			'joacubuploader_zend_db_adapter' => 'Zend\Db\Adapter\Adapter',
		),
	),
    'view_manager' => array(
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
    'asset_manager' => array(
        'resolver_configs' => array(
            'paths' => array(
                __DIR__ . '/../vendor',
            )
        ),
    ),
    'JoacubUploader' => array(
        'options' => array(
            'param_name' => 'files',
            // Set the following option to 'POST', if your server does not support
            // DELETE requests. This is a parameter sent to the client:
            'delete_type' => 'DELETE',
            'access_control_allow_origin' => '*',
            'access_control_allow_credentials' => false,
            'access_control_allow_methods' => array(
                'OPTIONS',
                'HEAD',
                'GET',
                'POST',
                'PUT',
                'PATCH',
                'DELETE'
            ),
            'access_control_allow_headers' => array(
                'Content-Type',
                'Content-Range',
                'Content-Disposition'
            ),
            // Enable to provide file downloads via GET requests to the PHP script:
            'download_via_php' => false,
            // Defines which files can be displayed inline when downloaded:
            'inline_file_types' => '/\.(gif|jpe?g|png)$/i',
            // Defines which files (based on their names) are accepted for upload:
            'accept_file_types' => '/.+$/i',
            // The php.ini settings upload_max_filesize and post_max_size
            // take precedence over the following max_file_size setting:
            'max_file_size' => null,
            'min_file_size' => 1,
            // The maximum number of files for the upload directory:
            'max_number_of_files' => null,
            // Image resolution restrictions:
            'max_width' => null,
            'max_height' => null,
            'min_width' => 1,
            'min_height' => 1,
            // Set the following option to false to enable resumable uploads:
            'discard_aborted_uploads' => true,
            // Set to true to rotate images based on EXIF meta data, if available:
            'orient_image' => false,
            'image_versions' => array(
                // Uncomment the following version to restrict the size of
                // uploaded images:
                /*
                '' => array(
                    'max_width' => 1920,
                    'max_height' => 1200,
                    'jpeg_quality' => 95
                ),
                */
                // Uncomment the following to create medium sized images:
                /*
                'medium' => array(
                    'max_width' => 800,
                    'max_height' => 600,
                    'jpeg_quality' => 80
                ),
                */
                'thumbnail' => array(
                    array(
                        'resize' => array(
                            'maxWidth' => 80, 
                            'maxHeight' => 80
                        )
                    )
                )
            ),
        	'title' => 'Uploader titulo de ejemplo',
        	'subtitle' => 'Subtitulo del uploader cambiar en <span class="label label-important">configuración</span> o cambiar a <span class="label">false</span> para no mostrar, lo mismo se puede hacer con el <span class="label label-important">titulo</span>',
            'uploaderTemplate' => 'ZfJoacubUploaderTwb/uploader',
            'uploadTemplatePhtml' => 'ZfJoacubUploaderTwb/template-upload',
            'downloadTemplatePhtml' => 'ZfJoacubUploaderTwb/template-download',
            'modalGalleryTemplatePhtml' => 'ZfJoacubUploaderTwb/modal-gallery',
            'maxFileSize' => 5000000,
            'acceptFileTypes' => new Expr('/(\.|\/)(gif|jpe?g|png)$/i'),
            'process' => array(
                array(
                    'action' => 'load',
                    'fileTypes' => new Expr('/^image\/(gif|jpeg|png)$/'),
                    'maxFileSize' => 20000000, // 20MB
                ),
                array(
                    'action' => 'resize',
                    'maxWidth' => 1440,
                    'maxHeight' => 900
                ),
                array(
                    'action' => 'save'
                )
            )
        )
    )
);