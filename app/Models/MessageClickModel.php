<?php
namespace App\Models;
use CodeIgniter\Model;

class MessageClickModel extends Model {
    protected $table = 'message_clicks';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['send_id','link_url','link_hash','clicked_at','ip_address','user_agent'];
}
