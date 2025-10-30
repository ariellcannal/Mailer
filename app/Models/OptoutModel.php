<?php
namespace App\Models;
use CodeIgniter\Model;

class OptoutModel extends Model {
    protected $table = 'optouts';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['contact_id','message_id','ip_address'];
}
