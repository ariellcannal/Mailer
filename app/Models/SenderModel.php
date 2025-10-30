<?php
namespace App\Models;
use CodeIgniter\Model;

class SenderModel extends Model {
    protected $table = 'senders';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['email','name','domain','ses_verified','ses_verification_token','dkim_verified','spf_verified','dmarc_verified','is_active'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
