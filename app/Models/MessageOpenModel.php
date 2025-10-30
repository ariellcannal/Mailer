<?php
namespace App\Models;
use CodeIgniter\Model;

class MessageOpenModel extends Model {
    protected $table = 'message_opens';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['send_id','opened_at','ip_address','user_agent'];
}
