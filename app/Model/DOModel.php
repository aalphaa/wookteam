<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Model
 * @package App\Model
 */
class DOModel extends Model
{

    public $original = [];

    public $timestamps = false;

    public function __construct($tabel = null, array $attributes = [])
    {
        parent::__construct($attributes);
        //
        if (is_string($tabel)) {
            $this->setTable($tabel);
        }
    }

    public function setTimestamps($timestamps)
    {
        $this->timestamps = $timestamps;
    }

}
