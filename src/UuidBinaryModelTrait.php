<?php

namespace Alsofronie\Uuid;

use Webpatser\Uuid\Uuid;

/*
 * This trait is to be used with the DB::statement('ALTER TABLE table_name ADD COLUMN id BINARY(16) PRIMARY KEY')
 * @package Alsofronie\Uuid
 * @author Alex Sofronie <alsofronie@gmail.com>
 * @license MIT
 */
trait UuidBinaryModelTrait
{
    /*
	 * This function is used internally by Eloquent models to test if the model has auto increment value
	 * @returns bool Always false
	 */
    public function getIncrementing()
    {
        return false;
    }

    /**
     * This function overwrites the default boot static method of Eloquent models. It will hook
     * the creation event with a simple closure to insert the UUID
     */
    public static function bootUuidBinaryModelTrait()
    {
        static::creating(function ($model) {
            // This is necessary because on \Illuminate\Database\Eloquent\Model::performInsert
            // will not check for $this->getIncrementing() but directly for $this->incrementing
            $model->incrementing = false;
            $uuidVersion = (!empty($model->uuidVersion) ? $model->uuidVersion : 4);   // defaults to 4
            $uuid = Uuid::generate($uuidVersion);
            $model->attributes[$model->getKeyName()] = (isset($model->optimized) && $model->optimized
                ? hex2bin($this->toOptimized($uuid->string)) : $uuid->bytes);
        }, 0);
    }

    /**
     * Gets the binary field as hex string ($model->id_string)
     * @return string The string representation of the binary field.
     */
    public function getIdStringAttribute()
    {
        return (isset($model->optimized) && $model->optimized)
            ? $this->toNormal(bin2hex($this->attributes['id'])) : bin2hex($this->attributes['id']);
    }

    /**
     * Modified find static function to accept both string and binary versions of uuid
     * @param  mixed $id       The id (binary or hex string)
     * @param  array $columns  The columns to be returned (defaults to *)
     * @return mixed           The model or null
     */
    public static function find($id, $columns = array('*'))
    {
        if (ctype_print($id)) {
            $idFinal = (isset($model->optimized) && $model->optimized)
            ? $this->toOptimized($id) : $id;
            return static::where('id', '=', hex2bin($idFinal)->first($columns);
        } else {
            return parent::where('id', '=', $id)->first($columns);
        }
    }

    public static function toOptimized($uuid)
    {
        return substr($uuid, 15, 4)
            . substr($uuid, 10, 4)
            . substr($uuid, 1, 8)
            . substr($uuid, 20, 4)
            . substr($uuid, 25);
    }

    public static function toNormal($uuid)
    {
        return substr($uuid, 9, 8) . '-'
            . substr($uuid, 5, 4) . '-'
            . substr($uuid, 1, 4) . '-'
            . substr($uuid, 17, 4) . '-'
            . substr($uuid, 21);
    }
}

