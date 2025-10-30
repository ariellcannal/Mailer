<?php
namespace App\Models;
use CodeIgniter\Model;

class CampaignModel extends Model {
    protected $table = 'campaigns';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = ['name','description','total_messages','total_sends','total_opens','total_clicks','total_bounces','total_optouts'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    public function increment(int $id, string $field, int $value = 1): bool {
        return $this->set($field, "$field + $value", false)->where('id', $id)->update();
    }
}
