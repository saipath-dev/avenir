<?php

namespace App\Models;

use CodeIgniter\Model;

class DocumentUploadModel extends Model
{
	protected $table = 'document_upload';
	protected $primaryKey = 'id';
	protected $allowedFields = [ 'tour_id','type','image_path','user','date','delete', 'delete_user', 'delete_date','status', 'accept_user', 'accept_date', 'reject_user', 'reject_date'];
	protected $returnType = 'array';
}
