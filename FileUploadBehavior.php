<?php
/**
 * Created by PhpStorm.
 * User: Sergiy
 * Company: http://web-logic.biz/
 * Email: sirogabox@gmail.com
 * Date: 19.08.14
 * Time: 12:12
 *
 * if you want work with images need connect image component in main.php and add extension CImageComponent  like this
 * http://www.yiiframework.com/extension/image
        'image'=>array(
            'class' => 'application.extensions.image.CImageComponent',
            'driver'=>'GD',
        ),
 * don't forget add to your form 'htmlOptions'=>array('enctype'=>'multipart/form-data'),
 * example of use
 * пример использования
 public function behaviors()
    {
        return array(
            'Upload'=>array(
                'class'=>'application.components.FileUploadBehavior',
                'attribute'=>'image',               // model attribute 
                'fileAlias'=>'webroot.upload.good', //directory path for saving files
                'returnAlias'=>'/upload/good/',     // return path
                'types'=>'jpg, jpeg, png',          // type files
                'defaultName'=>'no_image.jpg'       // default name if model save without file
            )
        );
    }
 */

class FileUploadBehavior extends CActiveRecordBehavior
{
    public $defaultName = null;
    public $returnAlias = null;
    public $deleteOriginal = true;
    public $fileAlias = 'webroot.upload';

    public $attribute = 'file';

    public $types = 'jpg, jpeg, png';

    public function beforeSave($event)
    {
        $file = CUploadedFile::getInstance($this->owner, $this->attribute);
        if ($file) {
            $tempName = uniqid().'.'.$file->getExtensionName();
            $dir = Yii::getPathOfAlias($this->fileAlias).DIRECTORY_SEPARATOR;
            if(!is_dir($dir)){
                mkdir($dir);
            }
            $file->saveAs($dir.$tempName);
            $this->owner->setAttribute($this->attribute, $tempName);
        } else
            $this->owner->setAttribute($this->attribute, $this->_old_file);
        if(!$this->owner->isNewRecord){
            if($this->owner->{$this->attribute}!==$this->_old_file){
                $this->deleteOldFile($this->_old_file);
            }
        }
        $event->isValid = true;
    }

    protected $_old_file;

    public function afterFind($event)
    {
        $this->_old_file = $this->owner->{$this->attribute};
    }

    public function afterDelete($event)
    {
        $file = $this->owner->{$this->attribute};
        $this->deleteOldFile($file);
    }

    public function attach($owner) {
        parent::attach($owner);
        $validators = $this->owner->getValidatorList();
        $validator = CValidator::createValidator('file', $this->owner, $this->attribute, array('types'=>$this->types, 'allowEmpty'=>true));
        $validators->add($validator);
    }

    public function deleteOldFile($file){

        if($file!==$this->defaultName){
            $dir = Yii::getPathOfAlias($this->fileAlias).DIRECTORY_SEPARATOR;
            $files = glob($dir.'*'.$file);
            $originalFile = $dir.$file;
            foreach ($files as $file) {
                if ($this->deleteOriginal==true){
                    @unlink($file);
                }else{
                    if( $file != $originalFile){
                        @unlink($file);
                    }
                }
            }
        }
    }

    //if file is image you can get this image(see in the head and connect extension)
    public function getImage($width,$height,$crop=true)
    {
        $image = $this->owner->{$this->attribute};
        if (empty($image))
            return false;
        $dir = Yii::getPathOfAlias($this->fileAlias).DIRECTORY_SEPARATOR;
        if(!is_file($dir.$image)){
            return Yii::app()->baseUrl.$this->returnAlias.$this->defaultName;
        }
        $name = $width.'_'.$height.'_'.$image;
        if (!is_file($dir.$name)) {
            self::resizeImage($width, $height, $dir.$image, $dir.$name,$crop);
        }
        return Yii::app()->baseUrl.$this->returnAlias.$name;
    }

    public static function resizeImage($width,$height,$scr,$dest,$crop=true)
    {
        $image = Yii::app()->image->load($scr);

        if($image->width > $width || $image->height > $height)
        {
            if (($image->width/$width) < ($image->height/$height))
                $image->resize($width, null, Image::AUTO);
            else
                $image->resize(null, $height, Image::AUTO);
        }
        if($crop){
            $image->crop($width, $height, 'center', 'center');
        }
        $image->save($dest);
    }
}
