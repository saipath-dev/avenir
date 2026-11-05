<?php
namespace App\Controllers;
use CodeIgniter\Controller;

class DbCheck extends Controller
{
    public function index()
    {
        $db = \Config\Database::connect();
        $query = $db->query("DESCRIBE job_role_assignments");
        $results = $query->getResultArray();
        echo "<pre>";
        print_r($results);
        echo "</pre>";

        // Also check if there are foreign keys
        $sql = "SELECT
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE
	REFERENCED_TABLE_SCHEMA = '" . $db->database . "' AND
    TABLE_NAME = 'job_role_assignments';";
        $query2 = $db->query($sql);
        $results2 = $query2->getResultArray();
        echo "<h3>Foreign Keys</h3><pre>";
        print_r($results2);
        echo "</pre>";
    }
}
