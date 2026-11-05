<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInquiryTables extends Migration
{
    public function up()
    {
        // 1. Update existing inquiry table
        $fields = [
            'start_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'end_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'vehicle' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'vehicle_name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'passenger_count' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'transport_only' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'hotel_only' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'manual_id' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'arrival_flight' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'departure_flight' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'arrival_flight_time' => [
                'type' => 'TIME',
                'null' => true,
            ],
            'departure_flight_time' => [
                'type' => 'TIME',
                'null' => true,
            ],
            'finalize' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'ref_no' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
        ];
        $this->forge->addColumn('inquiry', $fields);

        // 2. Create inquiry_timeline table
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'inquiry_id' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'inquiry_day' => [
                'type' => 'DECIMAL',
                'constraint' => '10,1',
            ],
            'tour_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'from_location' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'to_location' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'hotel_acc' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'hotel_name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'hotel_code' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'room_type' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'room_type_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'meal_code' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'gdistance' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0,
            ],
            'distance' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0,
            ],
            'extra_kilo' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0,
            ],
            'transfer_code' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'created_time' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_user' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'saved' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'is_sub' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'det_flag' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'confirm' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('inquiry_id');
        $this->forge->addKey('tour_date');
        $this->forge->createTable('inquiry_timeline');

        // 3. Create inquiry_sub_locations table
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'inquiry_id' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'date_id' => [
                'type' => 'DATE',
            ],
            'tour_row' => [
                'type' => 'DECIMAL',
                'constraint' => '10,1',
            ],
            'from' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
            ],
            'to' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
            ],
            'km' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0,
            ],
            'extra_km' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0,
            ],
            'estimate_km' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0,
            ],
            'ref_no' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('inquiry_id');
        $this->forge->addKey('date_id');
        $this->forge->createTable('inquiry_sub_locations');

        // 4. Create inquiry_activities table
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'inquiry_id' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'inquiry_row' => [
                'type' => 'DECIMAL',
                'constraint' => '10,1',
            ],
            'inquiry_date' => [
                'type' => 'DATE',
            ],
            'name' => [
                'type' => 'INT',
                'constraint' => 11,
                'comment' => 'Reference to entrance_fee.id',
            ],
            'code' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'place_name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'ref_no' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('inquiry_id');
        $this->forge->addKey('inquiry_date');
        $this->forge->createTable('inquiry_activities');

        // 5. Create inquiry_itinerary table
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'inquiry_id' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'inquiry_row' => [
                'type' => 'DECIMAL',
                'constraint' => '10,1',
            ],
            'inquiry_date' => [
                'type' => 'DATE',
            ],
            'order_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'area' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'bf' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'lunch' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'dinner' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'insert_date' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'ref_no' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'loc_ref' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('inquiry_id');
        $this->forge->addKey('inquiry_date');
        $this->forge->createTable('inquiry_itinerary');
    }

    public function down()
    {
        // Drop the new tables
        $this->forge->dropTable('inquiry_itinerary', true);
        $this->forge->dropTable('inquiry_activities', true);
        $this->forge->dropTable('inquiry_sub_locations', true);
        $this->forge->dropTable('inquiry_timeline', true);

        // Remove added columns from inquiry table
        $this->forge->dropColumn('inquiry', [
            'start_date',
            'end_date',
            'vehicle',
            'vehicle_name',
            'passenger_count',
            'transport_only',
            'hotel_only',
            'manual_id',
            'arrival_flight',
            'departure_flight',
            'arrival_flight_time',
            'departure_flight_time',
            'finalize',
            'ref_no',
        ]);
    }
}