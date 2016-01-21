<?php

/**
 * This is the model class for table "products".
 *
 * The followings are the available columns in table 'products':
 * @property integer $product_id
 * @property integer $category_id
 * @property string $product_name
 *
 * The followings are the available model relations:
 * @property Categories $category
 */
class Products extends CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'products';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		return array(
			array('category_id, product_name', 'required'),
			array('category_id', 'numerical', 'integerOnly'=>true),
			array('product_name', 'length', 'max'=>255),
			array('product_id, category_id, product_name', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		return array(
			'category' => array(self::BELONGS_TO, 'Categories', 'category_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'product_id' => 'Product',
			'category_id' => 'Category',
			'product_name' => 'Product Name',
		);
	}

	public function search()
	{
		$criteria=new CDbCriteria;

		$criteria->compare('product_id',$this->product_id);
		$criteria->compare('category_id',$this->category_id);
		$criteria->compare('product_name',$this->product_name,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	protected function beforeSave()
	{
		if (Categories::model()->findByPk($this->category_id)){
			$ret = true;
		} else {
			echo 'No category with id = ' . $this->category_id;
			$ret = false;
		}
		return $ret;
	}


	/**
	 * @param string $className active record class name.
	 * @return Products the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public function getAll($par = null)
	{
		$criteria = new CDbCriteria();

		if (isset($par['sort'])) {
			$criteria->order = 'product_name '. $par['sort'];
		}

		if (isset($par['category_id'])) {
			$criteria->condition = 'category_id = :category_id';
			$criteria->params[':category_id'] = $par['category_id'];
		}

		$all = $this->findAll($criteria);

		return $all;
	}
}
