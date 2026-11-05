<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInquiryModuleTables extends Migration
{
    public function up()
    {
        // Create inquiry_consignee table (similar to consignee but without room fields)
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'tour_id' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
            ],
            'consignee' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'country' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'k_code' => [
                'type' => 'VARCHAR',
                'constraint' => '10',
                'null' => true,
            ],
            'mobile' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'agent_ref' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'transport_agent' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'transport_agent_name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'guide' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'guide_name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
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
            'vehicle_rate' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
            ],
            'passenger_count' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'start_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'end_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'create_user' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'create_time' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'manual_id' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'agent' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'agent_name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'arrival_flight' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'departure_flight' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
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
            'hotel_only' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'transport_only' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'finalize' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'finalize_txt' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'is_template' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'ref_no' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('tour_id');
        $this->forge->addKey(['start_date', 'end_date']);
        $this->forge->createTable('inquiry_consignee');

        // Create inquiry_quotations table (similar to tour_quotations but without price fields)
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'quotation_id' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
            ],
            'quotation_day' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
            ],
            'distance_type' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'transfer_code' => [
                'type' => 'INT',
                'constraint' => 11,
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
            'gdistance' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
            ],
            'distance' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
            ],
            'extra_kilo' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
            ],
            'hotel_code' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'meal_code' => [
                'type' => 'VARCHAR',
                'constraint' => '10',
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
            'room_type' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'room_type_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'tour_date' => [
                'type' => 'DATE',
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
            'det_flag' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
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
            'confirm' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'finalize' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('quotation_id');
        $this->forge->addKey('tour_date');
        $this->forge->createTable('inquiry_quotations');

        // Create inquiry_sub_locations table
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'quotation_id' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
            ],
            'date_id' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
            ],
            'from' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'to' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'km' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
            ],
            'tour_row' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'extra_km' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
            ],
            'estimate_km' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
            ],
            'ref_no' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('quotation_id');
        $this->forge->addKey('date_id');
        $this->forge->createTable('inquiry_sub_locations');

        // Create inquiry_activities table (without fee field)
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'tour_id' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
            ],
            'tour_row' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'tour_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'code' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'ref_no' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('tour_id');
        $this->forge->addKey('tour_date');
        $this->forge->createTable('inquiry_activities');

        // Create inquiry_itinerary table
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'tour_id' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
            ],
            'tour_row' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'tour_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'order_id' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
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
                'constraint' => '10',
                'null' => true,
            ],
            'lunch' => [
                'type' => 'VARCHAR',
                'constraint' => '10',
                'null' => true,
            ],
            'dinner' => [
                'type' => 'VARCHAR',
                'constraint' => '10',
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
        $this->forge->addKey('tour_id');
        $this->forge->addKey('tour_date');
        $this->forge->createTable('inquiry_itinerary');

        // Create inquiry_passenger_details table
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'pass_name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'tour_id' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('tour_id');
        $this->forge->createTable('inquiry_passenger_details');
    }

    public function down()
    {
        $this->forge->dropTable('inquiry_consignee', true);
        $this->forge->dropTable('inquiry_quotations', true);
        $this->forge->dropTable('inquiry_sub_locations', true);
        $this->forge->dropTable('inquiry_activities', true);
        $this->forge->dropTable('inquiry_itinerary', true);
        $this->forge->dropTable('inquiry_passenger_details', true);
    }
}