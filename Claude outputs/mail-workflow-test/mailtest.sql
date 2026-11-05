/*M!999999\- enable the sandbox mode */ 

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `acc_selct`;
/*!50001 DROP VIEW IF EXISTS `acc_selct`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `acc_selct` AS SELECT
 1 AS `B_Code`,
  1 AS `BName`,
  1 AS `balflag`,
  1 AS `T_Code`,
  1 AS `TName`,
  1 AS `HName`,
  1 AS `H_Code`,
  1 AS `P_Code`,
  1 AS `PName`,
  1 AS `L_Code` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `account_basement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_basement` (
  `B_Code` smallint(6) NOT NULL,
  `BName` varchar(40) NOT NULL,
  `balflag` int(11) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`) USING BTREE,
  KEY `idx_B_Code` (`B_Code`) USING BTREE,
  KEY `indx_B_Code` (`B_Code`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `account_basement` DISABLE KEYS */;
/*!40000 ALTER TABLE `account_basement` ENABLE KEYS */;
DROP TABLE IF EXISTS `account_heading`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_heading` (
  `H_Code` smallint(6) NOT NULL,
  `HName` varchar(100) NOT NULL,
  `B_Code` smallint(6) NOT NULL,
  `T_Code` smallint(6) NOT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`) USING BTREE,
  KEY `idx_H_Code` (`H_Code`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `account_heading` DISABLE KEYS */;
/*!40000 ALTER TABLE `account_heading` ENABLE KEYS */;
DROP TABLE IF EXISTS `account_numbers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_numbers` (
  `TR_Code` smallint(6) NOT NULL,
  `HR_Code` smallint(6) NOT NULL,
  `Transaction_ID` int(11) NOT NULL,
  `PR_Code` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `account_numbers` DISABLE KEYS */;
/*!40000 ALTER TABLE `account_numbers` ENABLE KEYS */;
DROP TABLE IF EXISTS `account_posting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_posting` (
  `P_Code` smallint(6) NOT NULL,
  `PName` varchar(240) NOT NULL,
  `B_Code` smallint(6) NOT NULL,
  `T_Code` smallint(6) NOT NULL,
  `H_Code` smallint(6) NOT NULL,
  `L_Code` varchar(40) NOT NULL,
  `Link` varchar(40) DEFAULT NULL,
  `Pay` varchar(300) DEFAULT NULL,
  `acp` smallint(6) DEFAULT NULL,
  `deact` int(11) DEFAULT 0,
  `grn` int(11) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `type` int(11) DEFAULT 0,
  PRIMARY KEY (`ID`) USING BTREE,
  KEY `idx_L_Code` (`L_Code`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `account_posting` DISABLE KEYS */;
/*!40000 ALTER TABLE `account_posting` ENABLE KEYS */;
DROP TABLE IF EXISTS `account_runing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_runing` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `L_Code` varchar(100) NOT NULL DEFAULT '0',
  `Dr` decimal(19,2) NOT NULL DEFAULT 0.00,
  `Cr` decimal(19,2) NOT NULL DEFAULT 0.00,
  `Discription` varchar(300) NOT NULL DEFAULT '0',
  `Date` date DEFAULT NULL,
  `status` varchar(2) NOT NULL DEFAULT '0',
  `Cashier` varchar(150) NOT NULL DEFAULT '0',
  `Chk_No` varchar(300) NOT NULL DEFAULT '0',
  `Invoice_Num` varchar(150) NOT NULL DEFAULT '0',
  `DV` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `Linkacc` varchar(40) NOT NULL DEFAULT '0',
  `aflag` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `job` varchar(40) NOT NULL DEFAULT '0',
  `index_no` varchar(50) NOT NULL DEFAULT '0',
  `P_Cap` varchar(140) NOT NULL DEFAULT '0',
  `P_Month` date DEFAULT NULL,
  `Tax` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `srvc_no` int(11) NOT NULL DEFAULT 0,
  `Dt` datetime DEFAULT NULL,
  `fine_wave` int(11) NOT NULL DEFAULT 0,
  `fine_tax` int(11) NOT NULL DEFAULT 0,
  `fine_amount` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `fine_date` date DEFAULT NULL,
  `Recpt_No` int(11) NOT NULL DEFAULT 0,
  `Fee` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `Effective_Date` date DEFAULT NULL,
  `Slip_No` varchar(250) NOT NULL DEFAULT '',
  `BankFlg` int(11) NOT NULL DEFAULT 0,
  `Fee_Dis` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `DiscountFlg` int(11) NOT NULL DEFAULT 0,
  `fine_remove` int(11) NOT NULL DEFAULT 0,
  `Bank_Name` varchar(50) NOT NULL DEFAULT '',
  `Chk_Date` date DEFAULT NULL,
  `Bank_Inv` int(11) NOT NULL DEFAULT 0,
  `Bank_Date` date DEFAULT NULL,
  `Card_No` char(8) NOT NULL DEFAULT '',
  `shift_id` bigint(20) NOT NULL DEFAULT 0,
  `InvCancel` int(11) NOT NULL DEFAULT 0,
  `P_Cat` int(11) NOT NULL DEFAULT 0,
  `InvCancelNote` varchar(500) NOT NULL DEFAULT '',
  `Payment_Grade` int(11) NOT NULL DEFAULT 0,
  `JE_INV` int(11) DEFAULT NULL,
  `Sup_Invoice` int(11) DEFAULT NULL,
  `br_code` varchar(255) DEFAULT NULL,
  `repostlogin` varchar(255) DEFAULT NULL,
  `bankst_flag` int(11) NOT NULL DEFAULT 0,
  `New_L_Code` varchar(255) DEFAULT NULL,
  `repostdate` date DEFAULT NULL,
  `inv_total` varchar(50) NOT NULL,
  `dollarrate` double DEFAULT 0,
  `dollar` double DEFAULT 0,
  `advances_pay` int(11) DEFAULT 0,
  `payee` varchar(255) DEFAULT NULL,
  `date_changed` int(11) DEFAULT 0,
  `date_changed_user` varchar(255) DEFAULT NULL,
  `bank_rec` int(11) DEFAULT 0,
  `hotel_ref` varchar(100) DEFAULT NULL,
  `dollar_cr` decimal(10,3) DEFAULT 0.000,
  `dollarrate_cr` decimal(10,3) DEFAULT 0.000,
  KEY `index_no` (`index_no`) USING BTREE,
  KEY `L_Code` (`L_Code`) USING BTREE,
  KEY `Invoice_Num` (`Invoice_Num`) USING BTREE,
  KEY `Recpt_No` (`Recpt_No`) USING BTREE,
  KEY `P_Cap` (`P_Cap`) USING BTREE,
  KEY `P_Month` (`P_Month`) USING BTREE,
  KEY `ID` (`ID`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `account_runing` DISABLE KEYS */;
/*!40000 ALTER TABLE `account_runing` ENABLE KEYS */;
DROP TABLE IF EXISTS `account_runing_2024`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_runing_2024` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `L_Code` varchar(100) NOT NULL DEFAULT '0',
  `Dr` decimal(19,2) NOT NULL DEFAULT 0.00,
  `Cr` decimal(19,2) NOT NULL DEFAULT 0.00,
  `Discription` varchar(300) NOT NULL DEFAULT '0',
  `Date` date DEFAULT NULL,
  `status` varchar(2) NOT NULL DEFAULT '0',
  `Cashier` varchar(150) NOT NULL DEFAULT '0',
  `Chk_No` varchar(300) NOT NULL DEFAULT '0',
  `Invoice_Num` varchar(150) NOT NULL DEFAULT '0',
  `DV` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `Linkacc` varchar(40) NOT NULL DEFAULT '0',
  `aflag` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `job` varchar(40) NOT NULL DEFAULT '0',
  `index_no` varchar(50) NOT NULL DEFAULT '0',
  `P_Cap` varchar(140) NOT NULL DEFAULT '0',
  `P_Month` date DEFAULT NULL,
  `Tax` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `srvc_no` int(11) NOT NULL DEFAULT 0,
  `Dt` datetime DEFAULT NULL,
  `fine_wave` int(11) NOT NULL DEFAULT 0,
  `fine_tax` int(11) NOT NULL DEFAULT 0,
  `fine_amount` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `fine_date` date DEFAULT NULL,
  `Recpt_No` int(11) NOT NULL DEFAULT 0,
  `Fee` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `Effective_Date` date DEFAULT NULL,
  `Slip_No` varchar(250) NOT NULL DEFAULT '',
  `BankFlg` int(11) NOT NULL DEFAULT 0,
  `Fee_Dis` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `DiscountFlg` int(11) NOT NULL DEFAULT 0,
  `fine_remove` int(11) NOT NULL DEFAULT 0,
  `Bank_Name` varchar(50) NOT NULL DEFAULT '',
  `Chk_Date` date DEFAULT NULL,
  `Bank_Inv` int(11) NOT NULL DEFAULT 0,
  `Bank_Date` date DEFAULT NULL,
  `Card_No` char(8) NOT NULL DEFAULT '',
  `shift_id` bigint(20) NOT NULL DEFAULT 0,
  `InvCancel` int(11) NOT NULL DEFAULT 0,
  `P_Cat` int(11) NOT NULL DEFAULT 0,
  `InvCancelNote` varchar(500) NOT NULL DEFAULT '',
  `Payment_Grade` int(11) NOT NULL DEFAULT 0,
  `JE_INV` int(11) DEFAULT NULL,
  `Sup_Invoice` int(11) DEFAULT NULL,
  `br_code` varchar(255) DEFAULT NULL,
  `repostlogin` varchar(255) DEFAULT NULL,
  `bankst_flag` int(11) NOT NULL DEFAULT 0,
  `New_L_Code` varchar(255) DEFAULT NULL,
  `repostdate` date DEFAULT NULL,
  `inv_total` varchar(50) NOT NULL,
  `dollarrate` double DEFAULT 0,
  `dollar` double DEFAULT 0,
  `advances_pay` int(11) DEFAULT 0,
  `payee` varchar(255) DEFAULT NULL,
  `date_changed` int(11) DEFAULT 0,
  `date_changed_user` varchar(255) DEFAULT NULL,
  `bank_rec` int(11) DEFAULT 0,
  `hotel_ref` varchar(255) DEFAULT NULL,
  KEY `index_no` (`index_no`) USING BTREE,
  KEY `L_Code` (`L_Code`) USING BTREE,
  KEY `Invoice_Num` (`Invoice_Num`) USING BTREE,
  KEY `Recpt_No` (`Recpt_No`) USING BTREE,
  KEY `P_Cap` (`P_Cap`) USING BTREE,
  KEY `P_Month` (`P_Month`) USING BTREE,
  KEY `ID` (`ID`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `account_runing_2024` DISABLE KEYS */;
/*!40000 ALTER TABLE `account_runing_2024` ENABLE KEYS */;
DROP TABLE IF EXISTS `account_runing_bank_statetment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_runing_bank_statetment` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `L_Code` varchar(100) NOT NULL DEFAULT '0',
  `Dr` decimal(19,2) NOT NULL DEFAULT 0.00,
  `Cr` decimal(19,2) NOT NULL DEFAULT 0.00,
  `Discription` varchar(300) NOT NULL DEFAULT '0',
  `Date` date DEFAULT NULL,
  `status` varchar(2) NOT NULL DEFAULT '0',
  `Cashier` varchar(150) NOT NULL DEFAULT '0',
  `Chk_No` varchar(300) NOT NULL DEFAULT '0',
  `Invoice_Num` varchar(150) NOT NULL DEFAULT '0',
  `DV` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `Linkacc` varchar(40) NOT NULL DEFAULT '0',
  `aflag` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `job` varchar(40) NOT NULL DEFAULT '0',
  `index_no` varchar(50) NOT NULL DEFAULT '0',
  `P_Cap` varchar(140) NOT NULL DEFAULT '0',
  `P_Month` date DEFAULT NULL,
  `Tax` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `srvc_no` int(11) NOT NULL DEFAULT 0,
  `Dt` datetime DEFAULT NULL,
  `fine_wave` int(11) NOT NULL DEFAULT 0,
  `fine_tax` int(11) NOT NULL DEFAULT 0,
  `fine_amount` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `fine_date` date DEFAULT NULL,
  `Recpt_No` int(11) NOT NULL DEFAULT 0,
  `Fee` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `Effective_Date` date DEFAULT NULL,
  `Slip_No` varchar(250) NOT NULL DEFAULT '',
  `BankFlg` int(11) NOT NULL DEFAULT 0,
  `Fee_Dis` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `DiscountFlg` int(11) NOT NULL DEFAULT 0,
  `fine_remove` int(11) NOT NULL DEFAULT 0,
  `Bank_Name` varchar(50) NOT NULL DEFAULT '',
  `Chk_Date` date DEFAULT NULL,
  `Bank_Inv` int(11) NOT NULL DEFAULT 0,
  `Bank_Date` date DEFAULT NULL,
  `Card_No` char(8) NOT NULL DEFAULT '',
  `shift_id` bigint(20) NOT NULL DEFAULT 0,
  `InvCancel` int(11) NOT NULL DEFAULT 0,
  `P_Cat` int(11) NOT NULL DEFAULT 0,
  `InvCancelNote` varchar(500) NOT NULL DEFAULT '',
  `Payment_Grade` int(11) NOT NULL DEFAULT 0,
  `JE_INV` int(11) DEFAULT NULL,
  `Sup_Invoice` int(11) DEFAULT NULL,
  `br_code` varchar(255) DEFAULT NULL,
  `repostlogin` varchar(255) DEFAULT NULL,
  `bankst_flag` int(11) NOT NULL DEFAULT 0,
  `New_L_Code` varchar(255) DEFAULT NULL,
  `repostdate` date DEFAULT NULL,
  `inv_total` varchar(50) NOT NULL,
  `dollarrate` double DEFAULT 0,
  `dollar` double DEFAULT 0,
  `advances_pay` int(11) DEFAULT 0,
  `payee` varchar(255) DEFAULT NULL,
  `date_changed` int(11) DEFAULT 0,
  `date_changed_user` varchar(255) DEFAULT NULL,
  `bank_rec` int(11) DEFAULT 0,
  `hotel_ref` varchar(100) DEFAULT NULL,
  KEY `index_no` (`index_no`) USING BTREE,
  KEY `L_Code` (`L_Code`) USING BTREE,
  KEY `Invoice_Num` (`Invoice_Num`) USING BTREE,
  KEY `Recpt_No` (`Recpt_No`) USING BTREE,
  KEY `P_Cap` (`P_Cap`) USING BTREE,
  KEY `P_Month` (`P_Month`) USING BTREE,
  KEY `ID` (`ID`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `account_runing_bank_statetment` DISABLE KEYS */;
/*!40000 ALTER TABLE `account_runing_bank_statetment` ENABLE KEYS */;
DROP TABLE IF EXISTS `account_runing_copy1`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_runing_copy1` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `L_Code` varchar(100) NOT NULL DEFAULT '0',
  `Dr` decimal(19,2) NOT NULL DEFAULT 0.00,
  `Cr` decimal(19,2) NOT NULL DEFAULT 0.00,
  `Discription` varchar(300) NOT NULL DEFAULT '0',
  `Date` date DEFAULT NULL,
  `status` varchar(2) NOT NULL DEFAULT '0',
  `Cashier` varchar(150) NOT NULL DEFAULT '0',
  `Chk_No` varchar(300) NOT NULL DEFAULT '0',
  `Invoice_Num` varchar(150) NOT NULL DEFAULT '0',
  `DV` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `Linkacc` varchar(40) NOT NULL DEFAULT '0',
  `aflag` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `job` varchar(40) NOT NULL DEFAULT '0',
  `index_no` varchar(50) NOT NULL DEFAULT '0',
  `P_Cap` varchar(140) NOT NULL DEFAULT '0',
  `P_Month` date DEFAULT NULL,
  `Tax` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `srvc_no` int(11) NOT NULL DEFAULT 0,
  `Dt` datetime DEFAULT NULL,
  `fine_wave` int(11) NOT NULL DEFAULT 0,
  `fine_tax` int(11) NOT NULL DEFAULT 0,
  `fine_amount` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `fine_date` date DEFAULT NULL,
  `Recpt_No` int(11) NOT NULL DEFAULT 0,
  `Fee` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `Effective_Date` date DEFAULT NULL,
  `Slip_No` varchar(250) NOT NULL DEFAULT '',
  `BankFlg` int(11) NOT NULL DEFAULT 0,
  `Fee_Dis` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `DiscountFlg` int(11) NOT NULL DEFAULT 0,
  `fine_remove` int(11) NOT NULL DEFAULT 0,
  `Bank_Name` varchar(50) NOT NULL DEFAULT '',
  `Chk_Date` date DEFAULT NULL,
  `Bank_Inv` int(11) NOT NULL DEFAULT 0,
  `Bank_Date` date DEFAULT NULL,
  `Card_No` char(8) NOT NULL DEFAULT '',
  `shift_id` bigint(20) NOT NULL DEFAULT 0,
  `InvCancel` int(11) NOT NULL DEFAULT 0,
  `P_Cat` int(11) NOT NULL DEFAULT 0,
  `InvCancelNote` varchar(500) NOT NULL DEFAULT '',
  `Payment_Grade` int(11) NOT NULL DEFAULT 0,
  `JE_INV` int(11) DEFAULT NULL,
  `Sup_Invoice` int(11) DEFAULT NULL,
  `br_code` varchar(255) DEFAULT NULL,
  `repostlogin` varchar(255) DEFAULT NULL,
  `bankst_flag` int(11) NOT NULL DEFAULT 0,
  `New_L_Code` varchar(255) DEFAULT NULL,
  `repostdate` date DEFAULT NULL,
  `inv_total` varchar(50) NOT NULL,
  `dollarrate` double DEFAULT 0,
  `dollar` double DEFAULT 0,
  `advances_pay` int(11) DEFAULT 0,
  `payee` varchar(255) DEFAULT NULL,
  `date_changed` int(11) DEFAULT 0,
  `date_changed_user` varchar(255) DEFAULT NULL,
  `bank_rec` int(11) DEFAULT 0,
  KEY `index_no` (`index_no`) USING BTREE,
  KEY `L_Code` (`L_Code`) USING BTREE,
  KEY `Invoice_Num` (`Invoice_Num`) USING BTREE,
  KEY `Recpt_No` (`Recpt_No`) USING BTREE,
  KEY `P_Cap` (`P_Cap`) USING BTREE,
  KEY `P_Month` (`P_Month`) USING BTREE,
  KEY `ID` (`ID`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `account_runing_copy1` DISABLE KEYS */;
/*!40000 ALTER TABLE `account_runing_copy1` ENABLE KEYS */;
DROP TABLE IF EXISTS `account_runing_copy2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_runing_copy2` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `L_Code` varchar(100) NOT NULL DEFAULT '0',
  `Dr` decimal(19,2) NOT NULL DEFAULT 0.00,
  `Cr` decimal(19,2) NOT NULL DEFAULT 0.00,
  `Discription` varchar(300) NOT NULL DEFAULT '0',
  `Date` date DEFAULT NULL,
  `status` varchar(2) NOT NULL DEFAULT '0',
  `Cashier` varchar(150) NOT NULL DEFAULT '0',
  `Chk_No` varchar(300) NOT NULL DEFAULT '0',
  `Invoice_Num` varchar(150) NOT NULL DEFAULT '0',
  `DV` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `Linkacc` varchar(40) NOT NULL DEFAULT '0',
  `aflag` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `job` varchar(40) NOT NULL DEFAULT '0',
  `index_no` varchar(50) NOT NULL DEFAULT '0',
  `P_Cap` varchar(140) NOT NULL DEFAULT '0',
  `P_Month` date DEFAULT NULL,
  `Tax` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `srvc_no` int(11) NOT NULL DEFAULT 0,
  `Dt` datetime DEFAULT NULL,
  `fine_wave` int(11) NOT NULL DEFAULT 0,
  `fine_tax` int(11) NOT NULL DEFAULT 0,
  `fine_amount` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `fine_date` date DEFAULT NULL,
  `Recpt_No` int(11) NOT NULL DEFAULT 0,
  `Fee` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `Effective_Date` date DEFAULT NULL,
  `Slip_No` varchar(250) NOT NULL DEFAULT '',
  `BankFlg` int(11) NOT NULL DEFAULT 0,
  `Fee_Dis` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `DiscountFlg` int(11) NOT NULL DEFAULT 0,
  `fine_remove` int(11) NOT NULL DEFAULT 0,
  `Bank_Name` varchar(50) NOT NULL DEFAULT '',
  `Chk_Date` date DEFAULT NULL,
  `Bank_Inv` int(11) NOT NULL DEFAULT 0,
  `Bank_Date` date DEFAULT NULL,
  `Card_No` char(8) NOT NULL DEFAULT '',
  `shift_id` bigint(20) NOT NULL DEFAULT 0,
  `InvCancel` int(11) NOT NULL DEFAULT 0,
  `P_Cat` int(11) NOT NULL DEFAULT 0,
  `InvCancelNote` varchar(500) NOT NULL DEFAULT '',
  `Payment_Grade` int(11) NOT NULL DEFAULT 0,
  `JE_INV` int(11) DEFAULT NULL,
  `Sup_Invoice` int(11) DEFAULT NULL,
  `br_code` varchar(255) DEFAULT NULL,
  `repostlogin` varchar(255) DEFAULT NULL,
  `bankst_flag` int(11) NOT NULL DEFAULT 0,
  `New_L_Code` varchar(255) DEFAULT NULL,
  `repostdate` date DEFAULT NULL,
  `inv_total` varchar(50) NOT NULL,
  `dollarrate` double DEFAULT 0,
  `dollar` double DEFAULT 0,
  `advances_pay` int(11) DEFAULT 0,
  `payee` varchar(255) DEFAULT NULL,
  `date_changed` int(11) DEFAULT 0,
  `date_changed_user` varchar(255) DEFAULT NULL,
  `bank_rec` int(11) DEFAULT 0,
  `hotel_ref` varchar(100) DEFAULT NULL,
  KEY `index_no` (`index_no`) USING BTREE,
  KEY `L_Code` (`L_Code`) USING BTREE,
  KEY `Invoice_Num` (`Invoice_Num`) USING BTREE,
  KEY `Recpt_No` (`Recpt_No`) USING BTREE,
  KEY `P_Cap` (`P_Cap`) USING BTREE,
  KEY `P_Month` (`P_Month`) USING BTREE,
  KEY `ID` (`ID`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `account_runing_copy2` DISABLE KEYS */;
/*!40000 ALTER TABLE `account_runing_copy2` ENABLE KEYS */;
DROP TABLE IF EXISTS `account_runing_copy3`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_runing_copy3` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `L_Code` varchar(100) NOT NULL DEFAULT '0',
  `Dr` decimal(19,2) NOT NULL DEFAULT 0.00,
  `Cr` decimal(19,2) NOT NULL DEFAULT 0.00,
  `Discription` varchar(300) NOT NULL DEFAULT '0',
  `Date` date DEFAULT NULL,
  `status` varchar(2) NOT NULL DEFAULT '0',
  `Cashier` varchar(150) NOT NULL DEFAULT '0',
  `Chk_No` varchar(300) NOT NULL DEFAULT '0',
  `Invoice_Num` varchar(150) NOT NULL DEFAULT '0',
  `DV` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `Linkacc` varchar(40) NOT NULL DEFAULT '0',
  `aflag` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `job` varchar(40) NOT NULL DEFAULT '0',
  `index_no` varchar(50) NOT NULL DEFAULT '0',
  `P_Cap` varchar(140) NOT NULL DEFAULT '0',
  `P_Month` date DEFAULT NULL,
  `Tax` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `srvc_no` int(11) NOT NULL DEFAULT 0,
  `Dt` datetime DEFAULT NULL,
  `fine_wave` int(11) NOT NULL DEFAULT 0,
  `fine_tax` int(11) NOT NULL DEFAULT 0,
  `fine_amount` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `fine_date` date DEFAULT NULL,
  `Recpt_No` int(11) NOT NULL DEFAULT 0,
  `Fee` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `Effective_Date` date DEFAULT NULL,
  `Slip_No` varchar(250) NOT NULL DEFAULT '',
  `BankFlg` int(11) NOT NULL DEFAULT 0,
  `Fee_Dis` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `DiscountFlg` int(11) NOT NULL DEFAULT 0,
  `fine_remove` int(11) NOT NULL DEFAULT 0,
  `Bank_Name` varchar(50) NOT NULL DEFAULT '',
  `Chk_Date` date DEFAULT NULL,
  `Bank_Inv` int(11) NOT NULL DEFAULT 0,
  `Bank_Date` date DEFAULT NULL,
  `Card_No` char(8) NOT NULL DEFAULT '',
  `shift_id` bigint(20) NOT NULL DEFAULT 0,
  `InvCancel` int(11) NOT NULL DEFAULT 0,
  `P_Cat` int(11) NOT NULL DEFAULT 0,
  `InvCancelNote` varchar(500) NOT NULL DEFAULT '',
  `Payment_Grade` int(11) NOT NULL DEFAULT 0,
  `JE_INV` int(11) DEFAULT NULL,
  `Sup_Invoice` int(11) DEFAULT NULL,
  `br_code` varchar(255) DEFAULT NULL,
  `repostlogin` varchar(255) DEFAULT NULL,
  `bankst_flag` int(11) NOT NULL DEFAULT 0,
  `New_L_Code` varchar(255) DEFAULT NULL,
  `repostdate` date DEFAULT NULL,
  `inv_total` varchar(50) NOT NULL,
  `dollarrate` double DEFAULT 0,
  `dollar` double DEFAULT 0,
  `advances_pay` int(11) DEFAULT 0,
  `payee` varchar(255) DEFAULT NULL,
  `date_changed` int(11) DEFAULT 0,
  `date_changed_user` varchar(255) DEFAULT NULL,
  `bank_rec` int(11) DEFAULT 0,
  `hotel_ref` varchar(100) DEFAULT NULL,
  `dollar_cr` decimal(10,3) DEFAULT 0.000,
  `dollarrate_cr` decimal(10,3) DEFAULT 0.000,
  KEY `index_no` (`index_no`) USING BTREE,
  KEY `L_Code` (`L_Code`) USING BTREE,
  KEY `Invoice_Num` (`Invoice_Num`) USING BTREE,
  KEY `Recpt_No` (`Recpt_No`) USING BTREE,
  KEY `P_Cap` (`P_Cap`) USING BTREE,
  KEY `P_Month` (`P_Month`) USING BTREE,
  KEY `ID` (`ID`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `account_runing_copy3` DISABLE KEYS */;
/*!40000 ALTER TABLE `account_runing_copy3` ENABLE KEYS */;
DROP TABLE IF EXISTS `account_runing_copy_dil`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_runing_copy_dil` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `L_Code` varchar(100) NOT NULL DEFAULT '0',
  `Dr` decimal(19,2) NOT NULL DEFAULT 0.00,
  `Cr` decimal(19,2) NOT NULL DEFAULT 0.00,
  `Discription` varchar(300) NOT NULL DEFAULT '0',
  `Date` date DEFAULT NULL,
  `status` varchar(2) NOT NULL DEFAULT '0',
  `Cashier` varchar(150) NOT NULL DEFAULT '0',
  `Chk_No` varchar(300) NOT NULL DEFAULT '0',
  `Invoice_Num` varchar(150) NOT NULL DEFAULT '0',
  `DV` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `Linkacc` varchar(40) NOT NULL DEFAULT '0',
  `aflag` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `job` varchar(40) NOT NULL DEFAULT '0',
  `index_no` varchar(50) NOT NULL DEFAULT '0',
  `P_Cap` varchar(140) NOT NULL DEFAULT '0',
  `P_Month` date DEFAULT NULL,
  `Tax` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `srvc_no` int(11) NOT NULL DEFAULT 0,
  `Dt` datetime DEFAULT NULL,
  `fine_wave` int(11) NOT NULL DEFAULT 0,
  `fine_tax` int(11) NOT NULL DEFAULT 0,
  `fine_amount` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `fine_date` date DEFAULT NULL,
  `Recpt_No` int(11) NOT NULL DEFAULT 0,
  `Fee` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `Effective_Date` date DEFAULT NULL,
  `Slip_No` varchar(250) NOT NULL DEFAULT '',
  `BankFlg` int(11) NOT NULL DEFAULT 0,
  `Fee_Dis` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `DiscountFlg` int(11) NOT NULL DEFAULT 0,
  `fine_remove` int(11) NOT NULL DEFAULT 0,
  `Bank_Name` varchar(50) NOT NULL DEFAULT '',
  `Chk_Date` date DEFAULT NULL,
  `Bank_Inv` int(11) NOT NULL DEFAULT 0,
  `Bank_Date` date DEFAULT NULL,
  `Card_No` char(8) NOT NULL DEFAULT '',
  `shift_id` bigint(20) NOT NULL DEFAULT 0,
  `InvCancel` int(11) NOT NULL DEFAULT 0,
  `P_Cat` int(11) NOT NULL DEFAULT 0,
  `InvCancelNote` varchar(500) NOT NULL DEFAULT '',
  `Payment_Grade` int(11) NOT NULL DEFAULT 0,
  `JE_INV` int(11) DEFAULT NULL,
  `Sup_Invoice` int(11) DEFAULT NULL,
  `br_code` varchar(255) DEFAULT NULL,
  `repostlogin` varchar(255) DEFAULT NULL,
  `bankst_flag` int(11) NOT NULL DEFAULT 0,
  `New_L_Code` varchar(255) DEFAULT NULL,
  `repostdate` date DEFAULT NULL,
  `inv_total` varchar(50) NOT NULL,
  `dollarrate` double DEFAULT 0,
  `dollar` double DEFAULT 0,
  `advances_pay` int(11) DEFAULT 0,
  `payee` varchar(255) DEFAULT NULL,
  `date_changed` int(11) DEFAULT 0,
  `date_changed_user` varchar(255) DEFAULT NULL,
  `bank_rec` int(11) DEFAULT 0,
  `hotel_ref` varchar(100) DEFAULT NULL,
  KEY `index_no` (`index_no`) USING BTREE,
  KEY `L_Code` (`L_Code`) USING BTREE,
  KEY `Invoice_Num` (`Invoice_Num`) USING BTREE,
  KEY `Recpt_No` (`Recpt_No`) USING BTREE,
  KEY `P_Cap` (`P_Cap`) USING BTREE,
  KEY `P_Month` (`P_Month`) USING BTREE,
  KEY `ID` (`ID`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `account_runing_copy_dil` DISABLE KEYS */;
/*!40000 ALTER TABLE `account_runing_copy_dil` ENABLE KEYS */;
DROP TABLE IF EXISTS `account_runing_copy_yearend_pro_before`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_runing_copy_yearend_pro_before` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `L_Code` varchar(100) NOT NULL DEFAULT '0',
  `Dr` decimal(19,2) NOT NULL DEFAULT 0.00,
  `Cr` decimal(19,2) NOT NULL DEFAULT 0.00,
  `Discription` varchar(300) NOT NULL DEFAULT '0',
  `Date` date DEFAULT NULL,
  `status` varchar(2) NOT NULL DEFAULT '0',
  `Cashier` varchar(150) NOT NULL DEFAULT '0',
  `Chk_No` varchar(300) NOT NULL DEFAULT '0',
  `Invoice_Num` varchar(150) NOT NULL DEFAULT '0',
  `DV` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `Linkacc` varchar(40) NOT NULL DEFAULT '0',
  `aflag` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `job` varchar(40) NOT NULL DEFAULT '0',
  `index_no` varchar(50) NOT NULL DEFAULT '0',
  `P_Cap` varchar(140) NOT NULL DEFAULT '0',
  `P_Month` date DEFAULT NULL,
  `Tax` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `srvc_no` int(11) NOT NULL DEFAULT 0,
  `Dt` datetime DEFAULT NULL,
  `fine_wave` int(11) NOT NULL DEFAULT 0,
  `fine_tax` int(11) NOT NULL DEFAULT 0,
  `fine_amount` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `fine_date` date DEFAULT NULL,
  `Recpt_No` int(11) NOT NULL DEFAULT 0,
  `Fee` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `Effective_Date` date DEFAULT NULL,
  `Slip_No` varchar(250) NOT NULL DEFAULT '',
  `BankFlg` int(11) NOT NULL DEFAULT 0,
  `Fee_Dis` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `DiscountFlg` int(11) NOT NULL DEFAULT 0,
  `fine_remove` int(11) NOT NULL DEFAULT 0,
  `Bank_Name` varchar(50) NOT NULL DEFAULT '',
  `Chk_Date` date DEFAULT NULL,
  `Bank_Inv` int(11) NOT NULL DEFAULT 0,
  `Bank_Date` date DEFAULT NULL,
  `Card_No` char(8) NOT NULL DEFAULT '',
  `shift_id` bigint(20) NOT NULL DEFAULT 0,
  `InvCancel` int(11) NOT NULL DEFAULT 0,
  `P_Cat` int(11) NOT NULL DEFAULT 0,
  `InvCancelNote` varchar(500) NOT NULL DEFAULT '',
  `Payment_Grade` int(11) NOT NULL DEFAULT 0,
  `JE_INV` int(11) DEFAULT NULL,
  `Sup_Invoice` int(11) DEFAULT NULL,
  `br_code` varchar(255) DEFAULT NULL,
  `repostlogin` varchar(255) DEFAULT NULL,
  `bankst_flag` int(11) NOT NULL DEFAULT 0,
  `New_L_Code` varchar(255) DEFAULT NULL,
  `repostdate` date DEFAULT NULL,
  `inv_total` varchar(50) NOT NULL,
  `dollarrate` double DEFAULT 0,
  `dollar` double DEFAULT 0,
  `advances_pay` int(11) DEFAULT 0,
  `payee` varchar(255) DEFAULT NULL,
  `date_changed` int(11) DEFAULT 0,
  `date_changed_user` varchar(255) DEFAULT NULL,
  `bank_rec` int(11) DEFAULT 0,
  `hotel_ref` varchar(100) DEFAULT NULL,
  KEY `index_no` (`index_no`) USING BTREE,
  KEY `L_Code` (`L_Code`) USING BTREE,
  KEY `Invoice_Num` (`Invoice_Num`) USING BTREE,
  KEY `Recpt_No` (`Recpt_No`) USING BTREE,
  KEY `P_Cap` (`P_Cap`) USING BTREE,
  KEY `P_Month` (`P_Month`) USING BTREE,
  KEY `ID` (`ID`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `account_runing_copy_yearend_pro_before` DISABLE KEYS */;
/*!40000 ALTER TABLE `account_runing_copy_yearend_pro_before` ENABLE KEYS */;
DROP TABLE IF EXISTS `account_runing_del`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_runing_del` (
  `ID` int(11) NOT NULL,
  `L_Code` varchar(100) NOT NULL DEFAULT '0',
  `Dr` decimal(19,2) NOT NULL DEFAULT 0.00,
  `Cr` decimal(19,2) NOT NULL DEFAULT 0.00,
  `Discription` varchar(300) NOT NULL DEFAULT '0',
  `Date` date DEFAULT NULL,
  `status` varchar(2) NOT NULL DEFAULT '0',
  `Cashier` varchar(150) NOT NULL DEFAULT '0',
  `Chk_No` varchar(300) NOT NULL DEFAULT '0',
  `Invoice_Num` varchar(255) NOT NULL DEFAULT '0',
  `DV` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `Linkacc` varchar(40) NOT NULL DEFAULT '0',
  `aflag` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `job` varchar(40) NOT NULL DEFAULT '0',
  `index_no` varchar(50) NOT NULL DEFAULT '0',
  `P_Cap` varchar(140) NOT NULL DEFAULT '0',
  `P_Month` date DEFAULT NULL,
  `Tax` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `srvc_no` int(11) NOT NULL DEFAULT 0,
  `Dt` datetime DEFAULT NULL,
  `fine_wave` int(11) NOT NULL DEFAULT 0,
  `fine_tax` int(11) NOT NULL DEFAULT 0,
  `fine_amount` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `fine_date` date DEFAULT NULL,
  `Recpt_No` int(11) NOT NULL DEFAULT 0,
  `Fee` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `Effective_Date` date DEFAULT NULL,
  `Slip_No` varchar(250) NOT NULL DEFAULT '',
  `BankFlg` int(11) NOT NULL DEFAULT 0,
  `Fee_Dis` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `DiscountFlg` int(11) NOT NULL DEFAULT 0,
  `fine_remove` int(11) NOT NULL DEFAULT 0,
  `Bank_Name` varchar(50) NOT NULL DEFAULT '',
  `Chk_Date` date DEFAULT NULL,
  `Bank_Inv` int(11) NOT NULL DEFAULT 0,
  `Bank_Date` date DEFAULT NULL,
  `Card_No` char(8) NOT NULL DEFAULT '',
  `shift_id` bigint(20) NOT NULL DEFAULT 0,
  `InvCancel` int(11) NOT NULL DEFAULT 0,
  `P_Cat` int(11) NOT NULL DEFAULT 0,
  `InvCancelNote` varchar(500) NOT NULL DEFAULT '',
  `Payment_Grade` int(11) NOT NULL DEFAULT 0,
  `JE_INV` int(11) DEFAULT NULL,
  `Sup_Invoice` int(11) DEFAULT NULL,
  `br_code` varchar(255) DEFAULT NULL,
  `repostlogin` varchar(255) DEFAULT NULL,
  `bankst_flag` int(11) NOT NULL DEFAULT 0,
  `New_L_Code` varchar(100) DEFAULT NULL,
  `repostdate` date DEFAULT NULL,
  `inv_total` varchar(50) NOT NULL,
  `exchange_rate` decimal(10,2) DEFAULT 0.00,
  `dollar_amount` decimal(10,2) DEFAULT 0.00,
  `direct_customer` varchar(255) DEFAULT NULL,
  KEY `index_no` (`index_no`) USING BTREE,
  KEY `L_Code` (`L_Code`) USING BTREE,
  KEY `Invoice_Num` (`Invoice_Num`) USING BTREE,
  KEY `Recpt_No` (`Recpt_No`) USING BTREE,
  KEY `P_Cap` (`P_Cap`) USING BTREE,
  KEY `P_Month` (`P_Month`) USING BTREE,
  KEY `ID` (`ID`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `account_runing_del` DISABLE KEYS */;
/*!40000 ALTER TABLE `account_runing_del` ENABLE KEYS */;
DROP TABLE IF EXISTS `account_runing_q2`;
/*!50001 DROP VIEW IF EXISTS `account_runing_q2`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `account_runing_q2` AS SELECT
 1 AS `TourId`,
  1 AS `BName`,
  1 AS `TName`,
  1 AS `HName`,
  1 AS `PName`,
  1 AS `L_Code`,
  1 AS `Date`,
  1 AS `Dr`,
  1 AS `Cr`,
  1 AS `ID`,
  1 AS `balflag`,
  1 AS `Recpt_No`,
  1 AS `bal`,
  1 AS `Discription` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `account_runing_q21`;
/*!50001 DROP VIEW IF EXISTS `account_runing_q21`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `account_runing_q21` AS SELECT
 1 AS `BName`,
  1 AS `TName`,
  1 AS `HName`,
  1 AS `PName`,
  1 AS `Date`,
  1 AS `Dr`,
  1 AS `Cr`,
  1 AS `bal` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `account_runing_qq`;
/*!50001 DROP VIEW IF EXISTS `account_runing_qq`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `account_runing_qq` AS SELECT
 1 AS `BName`,
  1 AS `TName`,
  1 AS `HName`,
  1 AS `PName`,
  1 AS `L_Code`,
  1 AS `ID`,
  1 AS `Dr`,
  1 AS `Cr`,
  1 AS `Discription`,
  1 AS `Date`,
  1 AS `Cashier`,
  1 AS `status`,
  1 AS `Chk_No`,
  1 AS `DV`,
  1 AS `Invoice_Num`,
  1 AS `Linkacc`,
  1 AS `aflag`,
  1 AS `job`,
  1 AS `index_no`,
  1 AS `P_Cap`,
  1 AS `New_L_Code`,
  1 AS `P_Month`,
  1 AS `Recpt_No`,
  1 AS `Bank_Name`,
  1 AS `Chk_Date`,
  1 AS `Bank_Inv`,
  1 AS `Bank_Date`,
  1 AS `Card_No`,
  1 AS `shift_id`,
  1 AS `balflag`,
  1 AS `Sup_Invoice`,
  1 AS `bankst_flag`,
  1 AS `bank_rec`,
  1 AS `dollarrate`,
  1 AS `dollar`,
  1 AS `dollar_cr`,
  1 AS `dollarrate_cr` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `account_runing_usd`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_runing_usd` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `L_Code` varchar(100) NOT NULL DEFAULT '0',
  `Dr` decimal(19,2) NOT NULL DEFAULT 0.00,
  `Cr` decimal(19,2) NOT NULL DEFAULT 0.00,
  `Discription` varchar(300) NOT NULL DEFAULT '0',
  `Date` date DEFAULT NULL,
  `status` varchar(2) NOT NULL DEFAULT '0',
  `Cashier` varchar(150) NOT NULL DEFAULT '0',
  `Chk_No` varchar(300) NOT NULL DEFAULT '0',
  `Invoice_Num` varchar(150) NOT NULL DEFAULT '0',
  `DV` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `Linkacc` varchar(40) NOT NULL DEFAULT '0',
  `aflag` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `job` varchar(40) NOT NULL DEFAULT '0',
  `index_no` varchar(50) NOT NULL DEFAULT '0',
  `P_Cap` varchar(140) NOT NULL DEFAULT '0',
  `P_Month` date DEFAULT NULL,
  `Tax` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `srvc_no` int(11) NOT NULL DEFAULT 0,
  `Dt` datetime DEFAULT NULL,
  `fine_wave` int(11) NOT NULL DEFAULT 0,
  `fine_tax` int(11) NOT NULL DEFAULT 0,
  `fine_amount` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `fine_date` date DEFAULT NULL,
  `Recpt_No` int(11) NOT NULL DEFAULT 0,
  `Fee` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `Effective_Date` date DEFAULT NULL,
  `Slip_No` varchar(250) NOT NULL DEFAULT '',
  `BankFlg` int(11) NOT NULL DEFAULT 0,
  `Fee_Dis` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `DiscountFlg` int(11) NOT NULL DEFAULT 0,
  `fine_remove` int(11) NOT NULL DEFAULT 0,
  `Bank_Name` varchar(50) NOT NULL DEFAULT '',
  `Chk_Date` date DEFAULT NULL,
  `Bank_Inv` int(11) NOT NULL DEFAULT 0,
  `Bank_Date` date DEFAULT NULL,
  `Card_No` char(8) NOT NULL DEFAULT '',
  `shift_id` bigint(20) NOT NULL DEFAULT 0,
  `InvCancel` int(11) NOT NULL DEFAULT 0,
  `P_Cat` int(11) NOT NULL DEFAULT 0,
  `InvCancelNote` varchar(500) NOT NULL DEFAULT '',
  `Payment_Grade` int(11) NOT NULL DEFAULT 0,
  `JE_INV` int(11) DEFAULT NULL,
  `Sup_Invoice` int(11) DEFAULT NULL,
  `br_code` varchar(255) DEFAULT NULL,
  `repostlogin` varchar(255) DEFAULT NULL,
  `bankst_flag` int(11) NOT NULL DEFAULT 0,
  `New_L_Code` varchar(255) DEFAULT NULL,
  `repostdate` date DEFAULT NULL,
  `inv_total` varchar(50) NOT NULL,
  `dollarrate` double DEFAULT 0,
  `dollar` double DEFAULT 0,
  `advances_pay` int(11) DEFAULT 0,
  `payee` varchar(255) DEFAULT NULL,
  `date_changed` int(11) DEFAULT 0,
  `date_changed_user` varchar(255) DEFAULT NULL,
  `bank_rec` int(11) DEFAULT 0,
  `hotel_ref` varchar(100) DEFAULT NULL,
  `dollar_cr` decimal(10,3) DEFAULT 0.000,
  `dollarrate_cr` decimal(10,3) DEFAULT 0.000,
  KEY `index_no` (`index_no`) USING BTREE,
  KEY `L_Code` (`L_Code`) USING BTREE,
  KEY `Invoice_Num` (`Invoice_Num`) USING BTREE,
  KEY `Recpt_No` (`Recpt_No`) USING BTREE,
  KEY `P_Cap` (`P_Cap`) USING BTREE,
  KEY `P_Month` (`P_Month`) USING BTREE,
  KEY `ID` (`ID`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `account_runing_usd` DISABLE KEYS */;
/*!40000 ALTER TABLE `account_runing_usd` ENABLE KEYS */;
DROP TABLE IF EXISTS `account_titel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_titel` (
  `T_Code` smallint(6) NOT NULL,
  `TName` varchar(100) NOT NULL,
  `B_Code` smallint(6) NOT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`) USING BTREE,
  KEY `idx_T_Code` (`T_Code`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `account_titel` DISABLE KEYS */;
/*!40000 ALTER TABLE `account_titel` ENABLE KEYS */;
DROP TABLE IF EXISTS `bank_statement`;
/*!50001 DROP VIEW IF EXISTS `bank_statement`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `bank_statement` AS SELECT
 1 AS `BName`,
  1 AS `TName`,
  1 AS `HName`,
  1 AS `PName`,
  1 AS `Invoice_Num`,
  1 AS `New_L_Code`,
  1 AS `L_Code`,
  1 AS `Dr`,
  1 AS `Cr`,
  1 AS `Cashier`,
  1 AS `ID`,
  1 AS `Discription`,
  1 AS `Date`,
  1 AS `status`,
  1 AS `Chk_No`,
  1 AS `Linkacc`,
  1 AS `aflag`,
  1 AS `job`,
  1 AS `Sup_Invoice`,
  1 AS `bankst_flag`,
  1 AS `bank_rec` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `banks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `banks` (
  `bank_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `banks` DISABLE KEYS */;
/*!40000 ALTER TABLE `banks` ENABLE KEYS */;
DROP TABLE IF EXISTS `booking_option`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `booking_option` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_option` varchar(250) NOT NULL,
  `code` varchar(20) NOT NULL,
  `j_name` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `booking_option` DISABLE KEYS */;
/*!40000 ALTER TABLE `booking_option` ENABLE KEYS */;
DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tour_name` varchar(255) NOT NULL,
  `created_by` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL,
  `departure_date` date DEFAULT NULL,
  `arrival_date` date DEFAULT NULL,
  `classification` varchar(100) DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  `option` varchar(100) DEFAULT NULL,
  `destination` varchar(150) NOT NULL DEFAULT '',
  `departure_port` varchar(255) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `flight_number` varchar(100) DEFAULT NULL,
  `fare_base` varchar(100) DEFAULT NULL,
  `travel_type` enum('Abroad','Domestic') DEFAULT NULL,
  `location_type` enum('Location','Airport') DEFAULT NULL,
  `male_count` int(11) DEFAULT 0,
  `female_count` int(11) DEFAULT 0,
  `child_count` int(11) DEFAULT 0,
  `infant_count` int(11) DEFAULT 0,
  `total_count` int(11) NOT NULL DEFAULT 0,
  `booking_id` int(11) NOT NULL,
  `confirm` int(11) NOT NULL DEFAULT 0,
  `adult_count` int(11) NOT NULL DEFAULT 0,
  `child_count_tot` int(11) NOT NULL DEFAULT 0,
  `adult_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `child_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `adult_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `child_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `infant_tot` int(11) NOT NULL DEFAULT 0,
  `infant_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `infant_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `adult_sale_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `adult_sale_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `child_sale_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `child_sale_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `infant_sale_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `infant_sale_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `invoice_no` int(11) NOT NULL DEFAULT 0,
  `payment_date` date NOT NULL DEFAULT '0000-00-00',
  `supplier` varchar(100) NOT NULL DEFAULT '',
  `agent` varchar(100) NOT NULL DEFAULT '',
  `prim` varchar(100) NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0: Pending, 1: Issued, 2: Canceled',
  `cancel_note` varchar(150) NOT NULL,
  `pnrNumber` varchar(10) DEFAULT NULL,
  `airport_tax` decimal(10,2) DEFAULT NULL,
  `food_allowance` decimal(10,2) DEFAULT NULL,
  `seat_allowance` decimal(10,2) DEFAULT NULL,
  `baguage_allowance` decimal(10,2) DEFAULT NULL,
  `additional_charges` decimal(10,2) DEFAULT NULL,
  `extra_charges` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`extra_charges`)),
  `last_edited_by` varchar(150) DEFAULT NULL,
  `last_edited_date` date DEFAULT NULL,
  `invoiceAmounts` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`invoiceAmounts`)),
  `j_name` varchar(255) DEFAULT NULL,
  `j_flag` char(2) DEFAULT NULL,
  `ticket_status` varchar(50) DEFAULT NULL,
  `inv_cancel` int(11) DEFAULT 0,
  `total_cost` decimal(50,2) DEFAULT NULL,
  `total_sales` decimal(50,2) DEFAULT NULL,
  `soft_delete` int(11) DEFAULT 0,
  `other_charges` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`other_charges`)),
  `ticket_issue_date` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `branches` (
  `bankID` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;
DROP TABLE IF EXISTS `changes_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `changes_log` (
  `r_id` int(11) NOT NULL AUTO_INCREMENT,
  `id` int(11) DEFAULT NULL,
  `quotation_id` varchar(255) DEFAULT NULL,
  `quotation_day` varchar(255) DEFAULT NULL,
  `hotel_code` varchar(255) DEFAULT NULL,
  `meal_code` varchar(255) DEFAULT NULL,
  `hotel_acc` varchar(255) DEFAULT NULL,
  `hotel_name` varchar(255) DEFAULT NULL,
  `room_type` varchar(255) DEFAULT NULL,
  `room_type_id` int(11) DEFAULT NULL,
  `tour_date` date DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `sgl` decimal(10,2) DEFAULT NULL,
  `dbl` decimal(10,2) DEFAULT NULL,
  `tpl` decimal(10,2) DEFAULT NULL,
  `new_hotel_acc` varchar(255) DEFAULT NULL,
  `new_hotel_code` varchar(255) DEFAULT NULL,
  `created_time` datetime DEFAULT NULL,
  `created_user` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`r_id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `changes_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `changes_log` ENABLE KEYS */;
DROP TABLE IF EXISTS `chat_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chat_id` int(11) DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `attachment_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `chat_attachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `chat_attachments` ENABLE KEYS */;
DROP TABLE IF EXISTS `chats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent` varchar(255) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `message` longtext DEFAULT NULL,
  `from_agent` int(11) DEFAULT NULL,
  `seen` int(11) DEFAULT 0,
  `conversation_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `chats` DISABLE KEYS */;
/*!40000 ALTER TABLE `chats` ENABLE KEYS */;
DROP TABLE IF EXISTS `chk_filter`;
/*!50001 DROP VIEW IF EXISTS `chk_filter`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `chk_filter` AS SELECT
 1 AS `PName`,
  1 AS `SDr`,
  1 AS `SCr`,
  1 AS `Chk_No`,
  1 AS `Bal`,
  1 AS `L_Code`,
  1 AS `DV`,
  1 AS `index_no` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `chk_runing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chk_runing` (
  `Acc_No` varchar(50) NOT NULL,
  `Chk_No` varchar(300) NOT NULL,
  `Transaction_ID` int(11) NOT NULL,
  `Amount` decimal(19,4) NOT NULL,
  `Exp_Date` datetime NOT NULL,
  `Status` varchar(30) NOT NULL,
  `aflag` tinyint(3) unsigned NOT NULL,
  `Bank` varchar(50) NOT NULL DEFAULT '',
  `Dt` datetime DEFAULT NULL,
  `Recpt_Num` int(11) NOT NULL DEFAULT 0,
  `InvCancel` int(11) NOT NULL DEFAULT 0,
  `Invoice_Num` varchar(20) NOT NULL,
  `bank_date` date DEFAULT '1900-01-01',
  `return_bank` varchar(150) DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `chk_runing` DISABLE KEYS */;
/*!40000 ALTER TABLE `chk_runing` ENABLE KEYS */;
DROP TABLE IF EXISTS `cities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `district_id` int(11) NOT NULL,
  `name_en` varchar(45) DEFAULT NULL,
  `name_si` varchar(45) DEFAULT NULL,
  `name_ta` varchar(45) DEFAULT NULL,
  `sub_name_en` varchar(45) DEFAULT NULL,
  `sub_name_si` varchar(45) DEFAULT NULL,
  `sub_name_ta` varchar(45) DEFAULT NULL,
  `postcode` varchar(15) DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `cities` DISABLE KEYS */;
/*!40000 ALTER TABLE `cities` ENABLE KEYS */;
DROP TABLE IF EXISTS `consignee`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `consignee` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `tour_id` varchar(255) DEFAULT NULL,
  `consignee` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `k_code` varchar(255) DEFAULT NULL,
  `mobile` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `passenger_count` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `create_user` varchar(255) DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `vehicle` varchar(255) DEFAULT NULL,
  `finalize` int(11) DEFAULT 0,
  `vehicle_name` varchar(255) DEFAULT NULL,
  `vehicle_rate` decimal(10,2) DEFAULT 0.00,
  `transport_agent` varchar(255) DEFAULT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `transport_advance` int(11) DEFAULT 0,
  `manual_id` varchar(255) DEFAULT NULL,
  `agent` varchar(255) DEFAULT NULL,
  `agent_name` varchar(255) DEFAULT NULL,
  `arrival_flight` varchar(255) DEFAULT NULL,
  `departure_flight` varchar(255) DEFAULT NULL,
  `bank` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `arrival_flight_time` time DEFAULT NULL,
  `departure_flight_time` time DEFAULT NULL,
  `sgl` int(11) DEFAULT NULL,
  `dbl` int(11) DEFAULT NULL,
  `tpl` int(11) DEFAULT NULL,
  `dbl_twin` int(11) DEFAULT NULL,
  `hotel_only` int(11) DEFAULT 0,
  `ref_no` int(11) DEFAULT 0,
  `total_room_cost` decimal(10,2) DEFAULT 0.00,
  `total_transport_cost` decimal(10,2) DEFAULT 0.00,
  `total_mileage` decimal(10,2) DEFAULT 0.00,
  `bata_day_rate` decimal(10,2) DEFAULT 0.00,
  `bata_days` int(11) DEFAULT 0,
  `activity_fee_total` decimal(10,2) DEFAULT 0.00,
  `transport_only` int(11) DEFAULT 0,
  `transport_agent_name` varchar(255) DEFAULT NULL,
  `is_template` int(11) DEFAULT 0,
  `transport_settled` int(11) DEFAULT 0,
  `identical` varchar(255) DEFAULT NULL,
  `verify` int(11) DEFAULT 0,
  `log_sheet_mileage` decimal(10,2) DEFAULT 0.00,
  `guide` varchar(255) DEFAULT NULL,
  `guide_name` varchar(255) DEFAULT NULL,
  `finalize_txt` int(11) DEFAULT 0,
  `agent_ref` varchar(255) DEFAULT NULL,
  `amend_inv` varchar(255) DEFAULT NULL,
  `payment_complete` int(11) NOT NULL,
  `payment_complete_user` varchar(150) DEFAULT NULL,
  `payment_complete_date` date DEFAULT NULL,
  `payment_request` int(11) NOT NULL DEFAULT 0,
  `payment_request_user` varchar(100) DEFAULT NULL,
  `payment_request_date` date NOT NULL DEFAULT '2001-01-01',
  `tour_cancel` int(11) DEFAULT 0,
  `marked_complete` tinyint(1) NOT NULL DEFAULT 0,
  `marked_complete_user` varchar(100) DEFAULT NULL,
  `marked_complete_date` datetime DEFAULT NULL,
  `per_person_cost` decimal(10,2) DEFAULT NULL,
  `chat_conversation_id` int(11) DEFAULT NULL,
  `created_by_agent` tinyint(1) DEFAULT 0,
  `agent_phase_completion` enum('none','phase1','phase2','phase3') DEFAULT 'none',
  `agent_itinerary` text DEFAULT NULL,
  `inv_alert_status` int(11) DEFAULT 0,
  `accepted_user` varchar(255) DEFAULT NULL,
  `accepted_date` datetime DEFAULT NULL,
  `tour_code` varchar(255) DEFAULT NULL,
  `template_id` int(11) DEFAULT 0,
  `template_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `consignee` DISABLE KEYS */;
/*!40000 ALTER TABLE `consignee` ENABLE KEYS */;
DROP TABLE IF EXISTS `consignee_copy1`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `consignee_copy1` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `tour_id` varchar(255) DEFAULT NULL,
  `consignee` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `k_code` varchar(255) DEFAULT NULL,
  `mobile` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `passenger_count` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `create_user` varchar(255) DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `vehicle` varchar(255) DEFAULT NULL,
  `finalize` int(11) DEFAULT 0,
  `vehicle_name` varchar(255) DEFAULT NULL,
  `vehicle_rate` decimal(10,2) DEFAULT 0.00,
  `transport_agent` varchar(255) DEFAULT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `transport_advance` int(11) DEFAULT 0,
  `manual_id` varchar(255) DEFAULT NULL,
  `agent` varchar(255) DEFAULT NULL,
  `agent_name` varchar(255) DEFAULT NULL,
  `arrival_flight` varchar(255) DEFAULT NULL,
  `departure_flight` varchar(255) DEFAULT NULL,
  `bank` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `arrival_flight_time` time DEFAULT NULL,
  `departure_flight_time` time DEFAULT NULL,
  `sgl` int(11) DEFAULT NULL,
  `dbl` int(11) DEFAULT NULL,
  `tpl` int(11) DEFAULT NULL,
  `dbl_twin` int(11) DEFAULT NULL,
  `hotel_only` int(11) DEFAULT 0,
  `ref_no` int(11) DEFAULT 0,
  `total_room_cost` decimal(10,2) DEFAULT 0.00,
  `total_transport_cost` decimal(10,2) DEFAULT 0.00,
  `total_mileage` decimal(10,2) DEFAULT 0.00,
  `bata_day_rate` decimal(10,2) DEFAULT 0.00,
  `bata_days` int(11) DEFAULT 0,
  `activity_fee_total` decimal(10,2) DEFAULT 0.00,
  `transport_only` int(11) DEFAULT 0,
  `transport_agent_name` varchar(255) DEFAULT NULL,
  `is_template` int(11) DEFAULT 0,
  `transport_settled` int(11) DEFAULT 0,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `consignee_copy1` DISABLE KEYS */;
/*!40000 ALTER TABLE `consignee_copy1` ENABLE KEYS */;
DROP TABLE IF EXISTS `consignee_copy2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `consignee_copy2` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `tour_id` varchar(255) DEFAULT NULL,
  `consignee` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `k_code` varchar(255) DEFAULT NULL,
  `mobile` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `passenger_count` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `create_user` varchar(255) DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `vehicle` varchar(255) DEFAULT NULL,
  `finalize` int(11) DEFAULT 0,
  `vehicle_name` varchar(255) DEFAULT NULL,
  `vehicle_rate` decimal(10,2) DEFAULT 0.00,
  `transport_agent` varchar(255) DEFAULT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `transport_advance` int(11) DEFAULT 0,
  `manual_id` varchar(255) DEFAULT NULL,
  `agent` varchar(255) DEFAULT NULL,
  `agent_name` varchar(255) DEFAULT NULL,
  `arrival_flight` varchar(255) DEFAULT NULL,
  `departure_flight` varchar(255) DEFAULT NULL,
  `bank` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `arrival_flight_time` time DEFAULT NULL,
  `departure_flight_time` time DEFAULT NULL,
  `sgl` int(11) DEFAULT NULL,
  `dbl` int(11) DEFAULT NULL,
  `tpl` int(11) DEFAULT NULL,
  `dbl_twin` int(11) DEFAULT NULL,
  `hotel_only` int(11) DEFAULT 0,
  `ref_no` int(11) DEFAULT 0,
  `total_room_cost` decimal(10,2) DEFAULT 0.00,
  `total_transport_cost` decimal(10,2) DEFAULT 0.00,
  `total_mileage` decimal(10,2) DEFAULT 0.00,
  `bata_day_rate` decimal(10,2) DEFAULT 0.00,
  `bata_days` int(11) DEFAULT 0,
  `activity_fee_total` decimal(10,2) DEFAULT 0.00,
  `transport_only` int(11) DEFAULT 0,
  `transport_agent_name` varchar(255) DEFAULT NULL,
  `is_template` int(11) DEFAULT 0,
  `transport_settled` int(11) DEFAULT 0,
  `identical` varchar(255) DEFAULT NULL,
  `verify` int(11) DEFAULT 0,
  `log_sheet_mileage` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `consignee_copy2` DISABLE KEYS */;
/*!40000 ALTER TABLE `consignee_copy2` ENABLE KEYS */;
DROP TABLE IF EXISTS `conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `conversations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `tagged_tour` varchar(255) DEFAULT NULL,
  `tagged_inquiry` varchar(255) DEFAULT NULL,
  `status` int(11) DEFAULT 1,
  `last_seen` timestamp NULL DEFAULT NULL,
  `active_quotation_id` int(11) DEFAULT NULL,
  `quotation_status` enum('none','in_progress','completed','agent_completed') DEFAULT 'none',
  `time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `conversations` DISABLE KEYS */;
/*!40000 ALTER TABLE `conversations` ENABLE KEYS */;
DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `countries` (
  `country_id` int(11) NOT NULL AUTO_INCREMENT,
  `code` char(2) NOT NULL COMMENT 'Two-letter country code (ISO 3166-1 alpha-2)',
  `name` varchar(64) NOT NULL COMMENT 'English country name',
  `full_name` varchar(128) NOT NULL COMMENT 'Full English country name',
  `iso3` char(3) NOT NULL COMMENT 'Three-letter country code (ISO 3166-1 alpha-3)',
  `number` smallint(3) unsigned zerofill NOT NULL COMMENT 'Three-digit country number (ISO 3166-1 numeric)',
  `continent_code` char(2) NOT NULL,
  `display_order` int(11) NOT NULL DEFAULT 900,
  PRIMARY KEY (`country_id`) USING BTREE,
  UNIQUE KEY `idx_code` (`code`) USING BTREE,
  KEY `idx_continent_code` (`continent_code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `countries` DISABLE KEYS */;
/*!40000 ALTER TABLE `countries` ENABLE KEYS */;
DROP TABLE IF EXISTS `country`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `country` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `iso` char(2) NOT NULL,
  `name` varchar(80) NOT NULL,
  `nicename` varchar(80) NOT NULL,
  `iso3` char(3) DEFAULT NULL,
  `numcode` smallint(6) DEFAULT NULL,
  `phonecode` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `country` DISABLE KEYS */;
/*!40000 ALTER TABLE `country` ENABLE KEYS */;
DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Sup_Acc_No` varchar(50) DEFAULT NULL,
  `Name` varchar(240) NOT NULL DEFAULT '0',
  `Address` varchar(300) NOT NULL DEFAULT '0',
  `NIC` varchar(30) NOT NULL DEFAULT '0',
  `TP` varchar(50) DEFAULT NULL,
  `Mobile` varchar(50) DEFAULT '0',
  `flag` int(11) DEFAULT 0,
  `cr_lmt` decimal(19,4) DEFAULT 0.0000,
  `cr_dyz` double DEFAULT 0,
  `csh` smallint(6) DEFAULT 0,
  `chq` smallint(6) DEFAULT 0,
  `chq_fvr` longtext DEFAULT NULL,
  `rep_offce` varchar(50) DEFAULT '0',
  `rep_mobile` varchar(50) DEFAULT '0',
  `ac` smallint(6) DEFAULT NULL,
  `sup_cat` int(11) DEFAULT NULL,
  `ref_no` int(11) DEFAULT NULL,
  `dealer_acc` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `t_code_1` varchar(255) DEFAULT NULL,
  `t_code_2` varchar(255) DEFAULT NULL,
  `zipcode` varchar(255) DEFAULT NULL,
  `margin` int(11) DEFAULT NULL,
  `long_name` varchar(255) DEFAULT NULL,
  `prefix` varchar(255) DEFAULT NULL,
  `title` varchar(10) DEFAULT NULL,
  `surname` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `per_mobile` varchar(255) DEFAULT NULL,
  `per_tp` varchar(255) DEFAULT NULL,
  `per_t_code_1` varchar(255) DEFAULT NULL,
  `per_t_code_2` varchar(255) DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `register_id` int(11) DEFAULT NULL,
  `agent` int(11) NOT NULL DEFAULT 0,
  `passport_no` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ID`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
DROP TABLE IF EXISTS `daily_usd_rate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `daily_usd_rate` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rate_date` date NOT NULL,
  `rate` decimal(10,2) NOT NULL,
  `created_user` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `rate_date` (`rate_date`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `daily_usd_rate` DISABLE KEYS */;
/*!40000 ALTER TABLE `daily_usd_rate` ENABLE KEYS */;
DROP TABLE IF EXISTS `debit_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debit_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_no` varchar(255) DEFAULT NULL,
  `dr` varchar(255) DEFAULT NULL,
  `cr` varchar(255) DEFAULT NULL,
  `balance` varchar(255) DEFAULT NULL,
  `cancelled_date` datetime DEFAULT NULL,
  `cancelled_by` varchar(255) DEFAULT NULL,
  `invoice_log_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `debit_notes` DISABLE KEYS */;
/*!40000 ALTER TABLE `debit_notes` ENABLE KEYS */;
DROP TABLE IF EXISTS `debtor_invoice_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_invoice_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent` varchar(200) NOT NULL,
  `income_type` varchar(250) NOT NULL,
  `date` date NOT NULL,
  `description` varchar(250) NOT NULL,
  `room_cost` decimal(10,2) NOT NULL,
  `transport_cost` decimal(10,2) NOT NULL,
  `doller_rate` decimal(10,2) NOT NULL,
  `dollar_amount` decimal(10,2) NOT NULL,
  `credit_balance` decimal(10,2) NOT NULL,
  `invoice_no` varchar(255) NOT NULL,
  `tour_id` varchar(100) NOT NULL,
  `consignee` varchar(500) NOT NULL,
  `hotel_amount` decimal(10,0) NOT NULL,
  `activity_amount` decimal(10,0) NOT NULL,
  `activity_des` varchar(1000) NOT NULL,
  `hotel_des` varchar(1000) NOT NULL,
  `cancel` int(11) NOT NULL DEFAULT 0,
  `payment_lcode` varchar(50) NOT NULL,
  `cancel_user` varchar(150) NOT NULL,
  `cancel_date` date NOT NULL,
  `requested_user` varchar(255) DEFAULT NULL,
  `requested_date` date DEFAULT NULL,
  `requested_flag` int(11) DEFAULT NULL,
  `requested_reason` varchar(1000) DEFAULT NULL,
  `top_mng_approval_finalize` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `debtor_invoice_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `debtor_invoice_log` ENABLE KEYS */;
DROP TABLE IF EXISTS `debtor_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_receipts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `receipt_no` varchar(50) NOT NULL,
  `customer_id` varchar(50) NOT NULL,
  `payment_date` date NOT NULL,
  `cash_amount` decimal(15,2) DEFAULT 0.00,
  `bank_amount` decimal(15,2) DEFAULT 0.00,
  `card_amount` decimal(15,2) DEFAULT 0.00,
  `slip_no` varchar(50) DEFAULT NULL,
  `booking_no` varchar(50) DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `card_details` text DEFAULT NULL,
  `Date` datetime DEFAULT NULL,
  `tour_id` int(11) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `dollar` double DEFAULT NULL,
  `dollarrate` double DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `receipt_no` (`receipt_no`) USING BTREE,
  KEY `customer_id` (`customer_id`) USING BTREE,
  KEY `booking_no` (`booking_no`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `debtor_receipts` DISABLE KEYS */;
/*!40000 ALTER TABLE `debtor_receipts` ENABLE KEYS */;
DROP TABLE IF EXISTS `debtor_receipts_copy1`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_receipts_copy1` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `receipt_no` varchar(50) NOT NULL,
  `customer_id` varchar(50) NOT NULL,
  `payment_date` date NOT NULL,
  `cash_amount` decimal(15,2) DEFAULT 0.00,
  `bank_amount` decimal(15,2) DEFAULT 0.00,
  `card_amount` decimal(15,2) DEFAULT 0.00,
  `slip_no` varchar(50) DEFAULT NULL,
  `booking_no` varchar(50) DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `card_details` text DEFAULT NULL,
  `Date` datetime DEFAULT NULL,
  `tour_id` int(11) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `receipt_no` (`receipt_no`) USING BTREE,
  KEY `customer_id` (`customer_id`) USING BTREE,
  KEY `booking_no` (`booking_no`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `debtor_receipts_copy1` DISABLE KEYS */;
/*!40000 ALTER TABLE `debtor_receipts_copy1` ENABLE KEYS */;
DROP TABLE IF EXISTS `debtors_qq2`;
/*!50001 DROP VIEW IF EXISTS `debtors_qq2`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `debtors_qq2` AS SELECT
 1 AS `HName`,
  1 AS `SDr`,
  1 AS `SCr`,
  1 AS `Invoice_Num`,
  1 AS `status`,
  1 AS `Bal`,
  1 AS `L_Code`,
  1 AS `FirstOfDate`,
  1 AS `PName`,
  1 AS `index_no`,
  1 AS `Balance_30_Days`,
  1 AS `Balance_60_Days`,
  1 AS `Balance_90_Days`,
  1 AS `Balance_Over_90_Days` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `deptors_qq`;
/*!50001 DROP VIEW IF EXISTS `deptors_qq`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `deptors_qq` AS SELECT
 1 AS `HName`,
  1 AS `SDr`,
  1 AS `SCr`,
  1 AS `Invoice_Num`,
  1 AS `status`,
  1 AS `Bal`,
  1 AS `L_Code`,
  1 AS `FirstOfDate`,
  1 AS `PName`,
  1 AS `index_no`,
  1 AS `dollarrate`,
  1 AS `dollar` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `deptors_qq_dollar`;
/*!50001 DROP VIEW IF EXISTS `deptors_qq_dollar`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `deptors_qq_dollar` AS SELECT
 1 AS `HName`,
  1 AS `SDr`,
  1 AS `SCr`,
  1 AS `Invoice_Num`,
  1 AS `status`,
  1 AS `Bal`,
  1 AS `DDr`,
  1 AS `DCr`,
  1 AS `DBal`,
  1 AS `L_Code`,
  1 AS `FirstOfDate`,
  1 AS `PName`,
  1 AS `index_no`,
  1 AS `dollarrate`,
  1 AS `dollar`,
  1 AS `Discription`,
  1 AS `dollarrate_cr`,
  1 AS `dollar_cr` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `document_upload`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_upload` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tour_id` int(11) DEFAULT NULL,
  `type` int(11) DEFAULT NULL,
  `image_path` varchar(500) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `delete` int(11) DEFAULT 0,
  `delete_user` varchar(255) DEFAULT NULL,
  `delete_date` date DEFAULT NULL,
  `status` int(11) DEFAULT 0,
  `accept_user` varchar(255) DEFAULT NULL,
  `accept_date` date DEFAULT NULL,
  `reject_user` varchar(255) DEFAULT NULL,
  `reject_date` date DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `document_upload` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_upload` ENABLE KEYS */;
DROP TABLE IF EXISTS `driver_advance_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `driver_advance_request` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tour_id` int(11) DEFAULT NULL,
  `manual_id` varchar(255) DEFAULT NULL,
  `driver` varchar(255) DEFAULT NULL,
  `driver_name` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `flag` int(11) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `driver_advance_request` DISABLE KEYS */;
/*!40000 ALTER TABLE `driver_advance_request` ENABLE KEYS */;
DROP TABLE IF EXISTS `driver_allocation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `driver_allocation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tour_id` int(11) DEFAULT NULL,
  `manual_id` varchar(255) DEFAULT NULL,
  `driver` varchar(255) DEFAULT NULL,
  `driver_name` varchar(255) DEFAULT NULL,
  `allow_date` datetime DEFAULT NULL,
  `allow_user` varchar(255) DEFAULT NULL,
  `payment_settlement` int(11) DEFAULT 0,
  `request_allocation` int(11) DEFAULT 0,
  `requested_user` varchar(255) DEFAULT NULL,
  `requested_date` datetime DEFAULT NULL,
  `approved_user` varchar(255) DEFAULT NULL,
  `approved_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `driver_allocation` DISABLE KEYS */;
/*!40000 ALTER TABLE `driver_allocation` ENABLE KEYS */;
DROP TABLE IF EXISTS `driver_allocation_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `driver_allocation_audit` (
  `audit_id` int(11) NOT NULL AUTO_INCREMENT,
  `operation` enum('INSERT','UPDATE','DELETE') NOT NULL,
  `table_name` varchar(255) NOT NULL DEFAULT 'driver_allocation',
  `record_id` int(11) NOT NULL,
  `tour_id` int(11) DEFAULT NULL,
  `manual_id` varchar(255) DEFAULT NULL,
  `driver` varchar(255) DEFAULT NULL,
  `driver_name` varchar(255) DEFAULT NULL,
  `changed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `changed_by` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`audit_id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `driver_allocation_audit` DISABLE KEYS */;
/*!40000 ALTER TABLE `driver_allocation_audit` ENABLE KEYS */;
DROP TABLE IF EXISTS `driver_expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `driver_expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tour_id` varchar(50) NOT NULL,
  `agent` varchar(100) DEFAULT NULL,
  `agent_name` varchar(255) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `supplier` varchar(100) DEFAULT NULL,
  `supplier_name` varchar(255) DEFAULT NULL,
  `invoice_no` varchar(100) DEFAULT NULL,
  `expense_date` date DEFAULT NULL,
  `dollar_rate` decimal(10,2) DEFAULT 0.00,
  `dollar_amount` decimal(10,2) DEFAULT 0.00,
  `lkr_amount` decimal(15,2) DEFAULT 0.00,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected','posted','settled','compeled') DEFAULT 'pending',
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `approved_by` varchar(100) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejected_by` varchar(100) DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `acc_run_id` int(11) DEFAULT NULL COMMENT 'Reference to account_runing ID after approval',
  `posted_by` varchar(100) DEFAULT NULL,
  `posted_at` datetime DEFAULT NULL,
  `request_send` tinyint(1) DEFAULT 0 COMMENT '0=not sent, 1=sent',
  `request_send_by` varchar(100) DEFAULT NULL,
  `request_send_at` datetime DEFAULT NULL,
  `finalized_by` varchar(255) DEFAULT NULL,
  `finalized_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tour_id` (`tour_id`),
  KEY `idx_status` (`status`),
  KEY `idx_expense_date` (`expense_date`),
  KEY `idx_agent` (`agent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `driver_expenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `driver_expenses` ENABLE KEYS */;
DROP TABLE IF EXISTS `driver_meter_reading`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `driver_meter_reading` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tour_id` varchar(50) NOT NULL,
  `driver_acc` varchar(50) NOT NULL,
  `reading_date` date NOT NULL,
  `type` enum('start','end') NOT NULL,
  `km` decimal(10,2) DEFAULT NULL,
  `previous_km` decimal(10,2) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_override` tinyint(1) NOT NULL DEFAULT 0,
  `overridden_by` varchar(255) DEFAULT NULL,
  `overridden_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `created_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `driver_meter_reading` DISABLE KEYS */;
/*!40000 ALTER TABLE `driver_meter_reading` ENABLE KEYS */;
DROP TABLE IF EXISTS `driver_portal_expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `driver_portal_expenses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tour_id` varchar(100) NOT NULL,
  `tour_date_id` int(10) unsigned DEFAULT NULL,
  `agent` varchar(100) NOT NULL,
  `agent_name` varchar(255) DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `supplier` varchar(100) NOT NULL,
  `supplier_name` varchar(255) DEFAULT NULL,
  `invoice_no` varchar(255) NOT NULL,
  `expense_date` date NOT NULL,
  `dollar_rate` decimal(15,2) NOT NULL DEFAULT 0.00,
  `dollar_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `lkr_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `acc_no` varchar(100) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `request_send` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_driver_portal_expenses_tour_id` (`tour_id`),
  KEY `idx_driver_portal_expenses_status` (`status`),
  KEY `idx_driver_portal_expenses_request_send` (`request_send`),
  KEY `idx_driver_portal_expenses_tour_date_id` (`tour_date_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `driver_portal_expenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `driver_portal_expenses` ENABLE KEYS */;
DROP TABLE IF EXISTS `employee`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `emp_num` varchar(50) DEFAULT NULL,
  `empno` varchar(50) DEFAULT NULL,
  `Acc_No` varchar(40) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Tp` varchar(100) DEFAULT NULL,
  `Division` smallint(6) DEFAULT NULL,
  `payout_date` datetime DEFAULT NULL,
  `installment` decimal(19,4) DEFAULT NULL,
  `epf_no` varchar(100) DEFAULT NULL,
  `bank_acc` varchar(100) DEFAULT NULL,
  `extra` smallint(6) DEFAULT NULL,
  `Salary` decimal(19,4) DEFAULT NULL,
  `Link_Code` int(11) DEFAULT NULL,
  `in_t` datetime DEFAULT NULL,
  `Out_t` datetime DEFAULT NULL,
  `meal` decimal(19,4) DEFAULT NULL,
  `transport` decimal(19,4) DEFAULT NULL,
  `desig` varchar(100) DEFAULT NULL,
  `appoinment` datetime DEFAULT NULL,
  `cntrct` smallint(6) DEFAULT NULL,
  `Living` decimal(19,4) DEFAULT NULL,
  `Spec` decimal(19,4) DEFAULT NULL,
  `Allow` decimal(19,4) DEFAULT NULL,
  `Family` decimal(19,4) DEFAULT NULL,
  `Incentive` decimal(19,4) DEFAULT NULL,
  `Cloth` decimal(19,4) DEFAULT NULL,
  `Com` decimal(19,4) DEFAULT NULL,
  `BRA` decimal(19,4) DEFAULT NULL,
  `OT` decimal(19,4) DEFAULT NULL,
  `rate` smallint(6) DEFAULT NULL,
  `epf_basic` smallint(6) DEFAULT NULL,
  `Bbranch` varchar(100) DEFAULT NULL,
  `Nic` varchar(100) DEFAULT NULL,
  `epf_trans` smallint(6) DEFAULT NULL,
  `othr_nm` varchar(100) DEFAULT NULL,
  `bank` varchar(100) DEFAULT NULL,
  `Sft_installment` decimal(19,4) DEFAULT NULL,
  `annual` varchar(100) DEFAULT NULL,
  `sick` varchar(100) DEFAULT NULL,
  `casual` varchar(100) DEFAULT NULL,
  `Tot_allowance` decimal(19,4) DEFAULT NULL,
  `att_bonus` decimal(19,4) DEFAULT NULL,
  `soft_install` decimal(19,4) DEFAULT NULL,
  `address` varchar(150) DEFAULT NULL,
  `prof_pic` varchar(50) DEFAULT NULL,
  `Start_time` datetime DEFAULT NULL,
  `Service_ID` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `surname` varchar(250) DEFAULT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `tpOffice` varchar(50) DEFAULT NULL,
  `tpHome` varchar(50) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `gender` varchar(50) DEFAULT NULL,
  `emp_status` varchar(50) DEFAULT NULL,
  `sync_status` varchar(50) DEFAULT NULL,
  `updated_time` datetime DEFAULT NULL,
  `sync_time` datetime DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL,
  `is_active` int(11) DEFAULT 1,
  `lib_status` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `employee` DISABLE KEYS */;
INSERT INTO `employee` VALUES (1,'9001','9001','4-8-9-99001','TEST Mail Administrator',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL);
/*!40000 ALTER TABLE `employee` ENABLE KEYS */;
DROP TABLE IF EXISTS `entertainment_expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `entertainment_expenses` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `grand_total` decimal(10,2) DEFAULT NULL,
  `card_acc` varchar(255) DEFAULT NULL,
  `card_name` varchar(255) DEFAULT NULL,
  `ref_no` int(11) DEFAULT NULL,
  `flag` int(11) DEFAULT 0,
  `user` varchar(255) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `rec_user` varchar(255) DEFAULT NULL,
  `rec_username` varchar(255) DEFAULT NULL,
  `payment_method` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ID`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `entertainment_expenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `entertainment_expenses` ENABLE KEYS */;
DROP TABLE IF EXISTS `entertainment_expenses_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `entertainment_expenses_details` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Date` date DEFAULT NULL,
  `EntryDate` date DEFAULT NULL,
  `Description` varchar(1000) DEFAULT NULL,
  `Amount` decimal(10,2) DEFAULT NULL,
  `currency` varchar(255) DEFAULT NULL,
  `invoice_img_path` varchar(255) DEFAULT NULL,
  `ref_no` int(11) DEFAULT NULL,
  `flag` int(11) DEFAULT 0,
  `user` varchar(255) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `payment_method` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ID`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `entertainment_expenses_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `entertainment_expenses_details` ENABLE KEYS */;
DROP TABLE IF EXISTS `entrance_fee`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `entrance_fee` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `l_code` varchar(255) DEFAULT NULL,
  `location` varchar(255) NOT NULL,
  `entranceFee` decimal(10,2) NOT NULL,
  `img` varchar(255) DEFAULT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `direct_payment` tinyint(1) NOT NULL DEFAULT 0,
  `is_prepaid` int(11) DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `entrance_fee` DISABLE KEYS */;
/*!40000 ALTER TABLE `entrance_fee` ENABLE KEYS */;
DROP TABLE IF EXISTS `extra_charges_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `extra_charges_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `extra_charge` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `extra_charges_type` DISABLE KEYS */;
/*!40000 ALTER TABLE `extra_charges_type` ENABLE KEYS */;
DROP TABLE IF EXISTS `extra_kilometers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `extra_kilometers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `city` varchar(50) NOT NULL,
  `kilometers` float NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `extra_kilometers` DISABLE KEYS */;
/*!40000 ALTER TABLE `extra_kilometers` ENABLE KEYS */;
DROP TABLE IF EXISTS `farebase`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `farebase` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `description` varchar(250) NOT NULL,
  `j_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `farebase` DISABLE KEYS */;
/*!40000 ALTER TABLE `farebase` ENABLE KEYS */;
DROP TABLE IF EXISTS `grn_tkn`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `grn_tkn` (
  `sup` varchar(100) DEFAULT NULL,
  `inv` varchar(50) DEFAULT NULL,
  `stock_card` varchar(100) DEFAULT NULL,
  `itm` varchar(500) DEFAULT NULL,
  `rack` varchar(100) DEFAULT NULL,
  `qty` double DEFAULT NULL,
  `ucost` decimal(19,4) DEFAULT NULL,
  `ws_min` decimal(19,4) DEFAULT NULL,
  `ws_max` decimal(19,4) DEFAULT NULL,
  `rtl_min` decimal(19,4) DEFAULT NULL,
  `rtl_max` decimal(19,4) DEFAULT NULL,
  `modl` varchar(100) DEFAULT NULL,
  `avg` decimal(19,4) DEFAULT NULL,
  `reordr` int(11) DEFAULT NULL,
  `maxordr` smallint(6) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `tkn` int(11) DEFAULT NULL,
  `sub` decimal(19,4) DEFAULT NULL,
  `foc` double DEFAULT NULL,
  `ID` int(11) NOT NULL,
  `expire_date` datetime DEFAULT NULL,
  `List_price` decimal(19,4) DEFAULT NULL,
  `Dis_price` decimal(19,4) DEFAULT NULL,
  `vat` decimal(19,4) DEFAULT NULL,
  `item_discount` decimal(19,4) DEFAULT NULL,
  `sales_margin` decimal(19,4) DEFAULT NULL,
  `Vat_p` decimal(19,4) DEFAULT NULL,
  `nbt` decimal(19,4) DEFAULT NULL,
  `dis` decimal(19,4) DEFAULT NULL,
  `vat_dis` varchar(50) DEFAULT NULL,
  `tn` varchar(50) DEFAULT NULL,
  `job` bigint(20) DEFAULT NULL,
  `LocalP` int(11) DEFAULT NULL,
  `SN` longtext DEFAULT NULL,
  `po_no` int(11) DEFAULT 0,
  `total_price` decimal(20,4) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `grn_tkn` DISABLE KEYS */;
/*!40000 ALTER TABLE `grn_tkn` ENABLE KEYS */;
DROP TABLE IF EXISTS `grn_tkn_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `grn_tkn_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inv_no` varchar(500) NOT NULL,
  `sup_id` varchar(50) NOT NULL,
  `order_no` varchar(500) NOT NULL,
  `date` date NOT NULL,
  `flag` int(11) DEFAULT 0,
  `po_no` int(11) DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `grn_tkn_order` DISABLE KEYS */;
/*!40000 ALTER TABLE `grn_tkn_order` ENABLE KEYS */;
DROP TABLE IF EXISTS `group_profile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `group_profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(100) NOT NULL,
  `user` int(11) NOT NULL,
  `del` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `group_profile` DISABLE KEYS */;
INSERT INTO `group_profile` VALUES (1,'System Administrator',33740,0);
INSERT INTO `group_profile` VALUES (2,'Branch Admin',33740,1);
INSERT INTO `group_profile` VALUES (5,'END USER',33740,0);
INSERT INTO `group_profile` VALUES (9,'Accountant',33740,0);
INSERT INTO `group_profile` VALUES (10,'nhjhjhj ffg',33740,1);
INSERT INTO `group_profile` VALUES (11,'Accounts',33740,0);
INSERT INTO `group_profile` VALUES (12,'Sales',33740,0);
INSERT INTO `group_profile` VALUES (13,'Operational',1,0);
INSERT INTO `group_profile` VALUES (15,'Maintenance',1,0);
INSERT INTO `group_profile` VALUES (21,'Management',1,0);
INSERT INTO `group_profile` VALUES (24,'Driver',1,0);
INSERT INTO `group_profile` VALUES (25,'Admin',1,0);
INSERT INTO `group_profile` VALUES (26,'Quotation Desk',1,0);
INSERT INTO `group_profile` VALUES (27,'Hotel Desk',1,0);
INSERT INTO `group_profile` VALUES (28,'Transport & Crew Guide Allocation Desk',1,0);
/*!40000 ALTER TABLE `group_profile` ENABLE KEYS */;
DROP TABLE IF EXISTS `hotel_rate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hotel_rate` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hotel` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `hotel_code` varchar(255) DEFAULT NULL,
  `room_type` int(11) DEFAULT NULL,
  `sro` decimal(10,2) DEFAULT NULL,
  `sbb` decimal(10,2) DEFAULT NULL,
  `shb` decimal(10,2) DEFAULT NULL,
  `sfb` decimal(10,2) DEFAULT NULL,
  `sdate` date DEFAULT NULL,
  `edate` date DEFAULT NULL,
  `dro` decimal(10,2) DEFAULT NULL,
  `dbb` decimal(10,2) DEFAULT NULL,
  `dhb` decimal(10,2) DEFAULT NULL,
  `dfb` decimal(10,2) DEFAULT NULL,
  `dsdate` date DEFAULT NULL,
  `dedate` date DEFAULT NULL,
  `tro` decimal(10,2) DEFAULT NULL,
  `tbb` decimal(10,2) DEFAULT NULL,
  `thb` decimal(10,2) DEFAULT NULL,
  `tfb` decimal(10,2) DEFAULT NULL,
  `tsdate` date DEFAULT NULL,
  `tedate` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hotel_rate` DISABLE KEYS */;
/*!40000 ALTER TABLE `hotel_rate` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_audit` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entity` varchar(30) NOT NULL,
  `entity_id` int(10) unsigned NOT NULL,
  `action` enum('insert','update','delete','merge','unmerge','approve','supersede','project','migrate') NOT NULL,
  `old_json` mediumtext DEFAULT NULL,
  `new_json` mediumtext DEFAULT NULL,
  `import_id` int(10) unsigned DEFAULT NULL,
  `user` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_audit_entity` (`entity`,`entity_id`),
  KEY `idx_hr2_audit_import` (`import_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_audit` DISABLE KEYS */;
INSERT INTO `hr2_audit` VALUES (1,'hotel',1,'insert',NULL,'{\"name\":\"TEST HOTEL (dummy — not a real hotel)\",\"sup_acc_no\":\"6-9-11-99001\",\"hotel_code\":\"HTL-001\"}',NULL,'mailtestadmin','2026-10-08 13:49:16');
INSERT INTO `hr2_audit` VALUES (2,'hotel_contact',1,'insert',NULL,'{\"hotel_id\":1,\"department\":\"reservations\",\"name\":\"\",\"position\":\"\",\"email\":\"saipath29@gmail.com\",\"phone\":\"\",\"is_active\":1,\"is_primary_to\":1,\"is_default_cc\":0,\"is_supplier_account\":1,\"source\":\"known when the hotel was created (Reservations e-mail \\/ phone)\"}',NULL,'mailtestadmin','2026-10-08 13:49:16');
INSERT INTO `hr2_audit` VALUES (3,'hotel_contact',1,'update','{\"is_active\":1,\"is_primary_to\":1}','{\"is_active\":0,\"is_primary_to\":0,\"source\":\"edited\"}',NULL,'mailtestadmin','2026-10-08 13:49:16');
INSERT INTO `hr2_audit` VALUES (4,'hotel_contact',2,'insert',NULL,'{\"hotel_id\":1,\"department\":\"reservations\",\"name\":\"TEST reservations (Shantha)\",\"position\":\"TEST recipient\",\"email\":\"saipath29@gmail.com\",\"phone\":\"\",\"is_active\":1,\"is_primary_to\":1,\"is_default_cc\":0,\"is_supplier_account\":0,\"source\":\"entered on the Hotel Master\"}',NULL,'mailtestadmin','2026-10-08 13:49:16');
/*!40000 ALTER TABLE `hr2_audit` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_cancellation_rule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_cancellation_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` int(10) unsigned NOT NULL,
  `date_from` date DEFAULT NULL,
  `date_to` date DEFAULT NULL,
  `tier` enum('all','fit','group') NOT NULL DEFAULT 'all',
  `min_rooms` smallint(5) unsigned DEFAULT NULL,
  `max_rooms` smallint(5) unsigned DEFAULT NULL,
  `min_pax` smallint(5) unsigned DEFAULT NULL,
  `max_pax` smallint(5) unsigned DEFAULT NULL,
  `days_from` smallint(5) unsigned DEFAULT NULL,
  `days_to` smallint(5) unsigned NOT NULL DEFAULT 0,
  `charge_pct` decimal(5,2) DEFAULT NULL,
  `charge_nights` tinyint(3) unsigned DEFAULT NULL,
  `note` varchar(300) DEFAULT NULL,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_cancellation_rule` (`contract_id`,`sort_order`),
  CONSTRAINT `fk_hr2_cancellation_rule_contract` FOREIGN KEY (`contract_id`) REFERENCES `hr2_contract` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_cancellation_rule` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr2_cancellation_rule` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_child_rate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_child_rate` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` int(10) unsigned NOT NULL,
  `age_from` decimal(4,2) NOT NULL DEFAULT 0.00,
  `age_to` decimal(4,2) NOT NULL,
  `bed` enum('any','with_bed','no_bed') NOT NULL DEFAULT 'any',
  `meal_plan` varchar(2) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `pct` decimal(5,2) DEFAULT NULL,
  `pct_basis` enum('adult_rate','meal_rate','meal_supplement','room_rate') DEFAULT NULL,
  `max_children` tinyint(3) unsigned DEFAULT NULL,
  `room_type_id` int(10) unsigned DEFAULT NULL,
  `note` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_child_rate` (`contract_id`,`age_from`),
  CONSTRAINT `fk_hr2_child_rate_contract` FOREIGN KEY (`contract_id`) REFERENCES `hr2_contract` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_child_rate` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr2_child_rate` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_contract`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_contract` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hotel_id` int(10) unsigned NOT NULL,
  `name` varchar(160) NOT NULL,
  `plan_type` enum('contract','special_offer','tactical','early_bird','addendum','series','migrated') NOT NULL DEFAULT 'contract',
  `rate_code` varchar(60) DEFAULT NULL,
  `tier` enum('all','fit','group','wedding','long_stay') NOT NULL DEFAULT 'all',
  `tier_min_rooms` smallint(5) unsigned DEFAULT NULL,
  `tier_max_rooms` smallint(5) unsigned DEFAULT NULL,
  `tier_min_pax` smallint(5) unsigned DEFAULT NULL,
  `tier_max_pax` smallint(5) unsigned DEFAULT NULL,
  `priority` tinyint(4) NOT NULL DEFAULT 0,
  `applies_to` enum('both','quotation','payment') NOT NULL DEFAULT 'both',
  `parent_contract_id` int(10) unsigned DEFAULT NULL,
  `import_id` int(10) unsigned DEFAULT NULL,
  `valid_from` date NOT NULL,
  `valid_to` date NOT NULL,
  `book_from` date DEFAULT NULL,
  `book_to` date DEFAULT NULL,
  `currency` char(3) NOT NULL DEFAULT 'USD',
  `billing_currency` enum('same','LKR') NOT NULL DEFAULT 'same',
  `pricing_mode` enum('per_room','per_person','per_unit') NOT NULL DEFAULT 'per_room',
  `default_meal_plan` varchar(2) DEFAULT NULL,
  `taxes_included` tinyint(1) NOT NULL DEFAULT 1,
  `tax_pct` decimal(5,2) DEFAULT NULL,
  `tax_note` varchar(200) DEFAULT NULL,
  `status` enum('draft','approved','superseded','cancelled') NOT NULL DEFAULT 'draft',
  `source_type` enum('manual','excel','ai','migration','email') NOT NULL DEFAULT 'manual',
  `source_file` varchar(255) DEFAULT NULL,
  `received_at` date DEFAULT NULL,
  `contract_date` date DEFAULT NULL,
  `contract_type` varchar(40) DEFAULT NULL,
  `hotel_reference` varchar(80) DEFAULT NULL,
  `credit_facility` enum('unknown','yes','no') NOT NULL DEFAULT 'unknown',
  `min_stay` tinyint(3) unsigned DEFAULT NULL,
  `max_rooms_per_booking` smallint(5) unsigned DEFAULT NULL,
  `release_days` smallint(5) unsigned DEFAULT NULL,
  `tentative_hold_hours` smallint(5) unsigned DEFAULT NULL,
  `extra_bed_adult` decimal(10,2) DEFAULT NULL,
  `max_children_free` tinyint(3) unsigned DEFAULT NULL,
  `child_policy_text` varchar(800) DEFAULT NULL,
  `guide_policy_text` varchar(600) DEFAULT NULL,
  `foc_policy_text` varchar(400) DEFAULT NULL,
  `cancellation_text` varchar(1200) DEFAULT NULL,
  `payment_text` varchar(800) DEFAULT NULL,
  `terms_text` text DEFAULT NULL,
  `voucher_note` varchar(300) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `entered_by` varchar(100) DEFAULT NULL,
  `checked_by` varchar(100) DEFAULT NULL,
  `approved_by` varchar(100) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `superseded_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_contract_hotel` (`hotel_id`,`status`,`valid_from`,`valid_to`),
  KEY `idx_hr2_contract_parent` (`parent_contract_id`),
  KEY `idx_hr2_contract_import` (`import_id`),
  CONSTRAINT `fk_hr2_contract_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hr2_hotel` (`id`),
  CONSTRAINT `chk_hr2_contract_dates` CHECK (`valid_from` <= `valid_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_contract` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr2_contract` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_contract_market`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_contract_market` (
  `contract_id` int(10) unsigned NOT NULL,
  `market_code` varchar(10) NOT NULL,
  `mode` enum('include','exclude') NOT NULL DEFAULT 'include',
  PRIMARY KEY (`contract_id`,`market_code`),
  CONSTRAINT `fk_hr2_contract_market_contract` FOREIGN KEY (`contract_id`) REFERENCES `hr2_contract` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_contract_market` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr2_contract_market` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_extra`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_extra` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` int(10) unsigned NOT NULL,
  `category` enum('excursion','transfer','mice','wellness','other') NOT NULL DEFAULT 'other',
  `name` varchar(160) NOT NULL,
  `per` enum('person','room','booking','group','hour') NOT NULL DEFAULT 'person',
  `adult` decimal(10,2) DEFAULT NULL,
  `child` decimal(10,2) DEFAULT NULL,
  `currency` char(3) DEFAULT NULL,
  `note` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_extra` (`contract_id`),
  CONSTRAINT `fk_hr2_extra_contract` FOREIGN KEY (`contract_id`) REFERENCES `hr2_contract` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_extra` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr2_extra` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_foc_rule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_foc_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` int(10) unsigned NOT NULL,
  `basis` enum('pax','rooms') NOT NULL DEFAULT 'pax',
  `every_n` smallint(5) unsigned NOT NULL,
  `free_n` smallint(5) unsigned NOT NULL DEFAULT 1,
  `discount_pct` decimal(5,2) NOT NULL DEFAULT 100.00,
  `as_what` enum('any','half_double','single','guide_room') NOT NULL DEFAULT 'any',
  `note` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_foc_rule` (`contract_id`),
  CONSTRAINT `fk_hr2_foc_rule_contract` FOREIGN KEY (`contract_id`) REFERENCES `hr2_contract` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_foc_rule` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr2_foc_rule` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_guide_policy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_guide_policy` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` int(10) unsigned NOT NULL,
  `role` enum('guide','driver','tour_leader','any') NOT NULL DEFAULT 'guide',
  `meal_plan` varchar(2) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `currency` char(3) DEFAULT NULL,
  `pct_of` enum('sgl','dbl','room') DEFAULT NULL,
  `pct` decimal(5,2) DEFAULT NULL,
  `per` enum('person','room') NOT NULL DEFAULT 'room',
  `free_from_pax` smallint(5) unsigned DEFAULT NULL,
  `min_rooms` smallint(5) unsigned DEFAULT NULL,
  `accommodation` enum('hotel_room','guide_quarters','nearby','unknown') NOT NULL DEFAULT 'unknown',
  `note` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_guide_policy` (`contract_id`),
  CONSTRAINT `fk_hr2_guide_policy_contract` FOREIGN KEY (`contract_id`) REFERENCES `hr2_contract` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_guide_policy` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr2_guide_policy` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_hotel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_hotel` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(240) NOT NULL,
  `hotel_code` varchar(12) DEFAULT NULL,
  `primary_sup_acc_no` varchar(50) NOT NULL,
  `location_id` int(10) unsigned DEFAULT NULL,
  `chain` varchar(120) DEFAULT NULL,
  `star_category` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `website` varchar(160) DEFAULT NULL,
  `reservations_email` varchar(255) DEFAULT NULL,
  `reservations_phone` varchar(80) DEFAULT NULL,
  `sales_contact` varchar(160) DEFAULT NULL,
  `accounts_email` varchar(255) DEFAULT NULL,
  `phone` varchar(60) DEFAULT NULL,
  `check_in_time` varchar(8) DEFAULT NULL,
  `check_out_time` varchar(8) DEFAULT NULL,
  `currency` char(3) NOT NULL DEFAULT 'USD',
  `tax_treatment` enum('inclusive','exclusive','unknown') NOT NULL DEFAULT 'unknown',
  `tax_note` varchar(200) DEFAULT NULL,
  `bank_details` text DEFAULT NULL,
  `status` enum('active','inactive','merged') NOT NULL DEFAULT 'active',
  `merged_into_id` int(10) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `frequently_used` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hr2_hotel_primary_acc` (`primary_sup_acc_no`),
  UNIQUE KEY `uq_hr2_hotel_code` (`hotel_code`),
  KEY `idx_hr2_hotel_name` (`name`),
  KEY `idx_hr2_hotel_location` (`location_id`),
  KEY `idx_hr2_hotel_chain` (`chain`),
  KEY `idx_hr2_hotel_status` (`status`),
  CONSTRAINT `fk_hr2_hotel_location` FOREIGN KEY (`location_id`) REFERENCES `hr2_location` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_hotel` DISABLE KEYS */;
INSERT INTO `hr2_hotel` VALUES (1,'TEST HOTEL (dummy — not a real hotel)','HTL-001','6-9-11-99001',NULL,NULL,NULL,'TEST address — not a real hotel',NULL,'saipath29@gmail.com',NULL,NULL,NULL,NULL,NULL,NULL,'USD','unknown',NULL,NULL,'active',NULL,NULL,'2026-10-08 13:49:16','mailtestadmin',NULL,NULL,0);
/*!40000 ALTER TABLE `hr2_hotel` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_hotel_account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_hotel_account` (
  `sup_acc_no` varchar(50) NOT NULL,
  `hotel_id` int(10) unsigned NOT NULL,
  `legacy_name` varchar(240) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`sup_acc_no`),
  KEY `idx_hr2_hotel_account_hotel` (`hotel_id`),
  CONSTRAINT `fk_hr2_hotel_account_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hr2_hotel` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_hotel_account` DISABLE KEYS */;
INSERT INTO `hr2_hotel_account` VALUES ('6-9-11-99001',1,'TEST HOTEL (dummy — not a real hotel)',1);
/*!40000 ALTER TABLE `hr2_hotel_account` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_hotel_code`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_hotel_code` (
  `code` varchar(12) NOT NULL,
  `no` int(10) unsigned NOT NULL,
  `hotel_id` int(10) unsigned DEFAULT NULL,
  `sup_acc_no` varchar(50) DEFAULT NULL,
  `hotel_name_at` varchar(240) DEFAULT NULL,
  `how` varchar(40) NOT NULL,
  `assigned_at` datetime NOT NULL,
  `assigned_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`code`),
  UNIQUE KEY `uq_hr2_hotel_code_no` (`no`),
  UNIQUE KEY `uq_hr2_hotel_code_hotel` (`hotel_id`),
  KEY `idx_hr2_hotel_code_acc` (`sup_acc_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_hotel_code` DISABLE KEYS */;
INSERT INTO `hr2_hotel_code` VALUES ('HTL-001',1,1,'6-9-11-99001','TEST HOTEL (dummy — not a real hotel)','create','2026-10-08 13:49:16','mailtestadmin');
/*!40000 ALTER TABLE `hr2_hotel_code` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_hotel_contact`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_hotel_contact` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hotel_id` int(10) unsigned NOT NULL,
  `department` varchar(20) NOT NULL DEFAULT 'general',
  `name` varchar(120) DEFAULT NULL,
  `position` varchar(120) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `phone` varchar(60) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_primary_to` tinyint(1) NOT NULL DEFAULT 0,
  `is_default_cc` tinyint(1) NOT NULL DEFAULT 0,
  `is_supplier_account` tinyint(1) NOT NULL DEFAULT 0,
  `source` varchar(120) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_hotel_contact_hotel` (`hotel_id`,`is_active`),
  KEY `idx_hr2_hotel_contact_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_hotel_contact` DISABLE KEYS */;
INSERT INTO `hr2_hotel_contact` VALUES (1,1,'reservations',NULL,NULL,'saipath29@gmail.com',NULL,0,0,0,1,'known when the hotel was created (Reservations e-mail / phone)','2026-10-08 13:49:16','mailtestadmin','2026-10-08 13:49:16','mailtestadmin');
INSERT INTO `hr2_hotel_contact` VALUES (2,1,'reservations','TEST reservations (Shantha)','TEST recipient','saipath29@gmail.com',NULL,1,1,0,0,'entered on the Hotel Master','2026-10-08 13:49:16','mailtestadmin','2026-10-08 13:49:16','mailtestadmin');
/*!40000 ALTER TABLE `hr2_hotel_contact` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_hotel_proposal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_hotel_proposal` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hotel_id` int(10) unsigned NOT NULL,
  `kind` varchar(20) NOT NULL,
  `contact_id` int(10) unsigned DEFAULT NULL,
  `field` varchar(40) DEFAULT NULL,
  `proposed_json` text DEFAULT NULL,
  `current_json` text DEFAULT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `source` varchar(120) DEFAULT NULL,
  `status` varchar(12) NOT NULL DEFAULT 'to_confirm',
  `created_at` datetime NOT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `resolved_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_hotel_proposal_hotel` (`hotel_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_hotel_proposal` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr2_hotel_proposal` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_import`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_import` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hotel_id` int(10) unsigned DEFAULT NULL,
  `contract_id` int(10) unsigned DEFAULT NULL,
  `contract_ids` varchar(400) DEFAULT NULL,
  `source` enum('excel','ai','manual','migration','email') NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `status` enum('preview','committed','rolled_back','failed') NOT NULL DEFAULT 'preview',
  `rows_total` int(11) NOT NULL DEFAULT 0,
  `rows_ok` int(11) NOT NULL DEFAULT 0,
  `rows_warning` int(11) NOT NULL DEFAULT 0,
  `rows_error` int(11) NOT NULL DEFAULT 0,
  `payload` longtext DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `committed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_import_hotel` (`hotel_id`),
  KEY `idx_hr2_import_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_import` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr2_import` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_legacy_map`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_legacy_map` (
  `hotel_rate_id` int(11) NOT NULL,
  `hotel_id` int(10) unsigned NOT NULL,
  `room_type_id` int(10) unsigned NOT NULL,
  `date_from` date NOT NULL,
  `date_to` date NOT NULL,
  `contract_ids` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`hotel_rate_id`),
  KEY `idx_hr2_legacy_map_hotel` (`hotel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_legacy_map` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr2_legacy_map` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_location`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_location` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `region` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hr2_location_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_location` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr2_location` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_location_alias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_location_alias` (
  `alias` varchar(120) NOT NULL,
  `location_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`alias`),
  KEY `idx_hr2_location_alias_loc` (`location_id`),
  CONSTRAINT `fk_hr2_location_alias_loc` FOREIGN KEY (`location_id`) REFERENCES `hr2_location` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_location_alias` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr2_location_alias` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_market`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_market` (
  `code` varchar(10) NOT NULL,
  `name` varchar(80) NOT NULL,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_market` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr2_market` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_market_country`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_market_country` (
  `country_iso` char(2) NOT NULL,
  `country_name` varchar(80) NOT NULL,
  `market_code` varchar(10) NOT NULL,
  `note` varchar(200) DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`country_iso`),
  KEY `idx_hr2_market_country_market` (`market_code`),
  CONSTRAINT `fk_hr2_market_country_market` FOREIGN KEY (`market_code`) REFERENCES `hr2_market` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_market_country` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr2_market_country` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_meal_delta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_meal_delta` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` int(10) unsigned NOT NULL,
  `from_plan` enum('RO','BB','HB','FB','AI') NOT NULL,
  `to_plan` enum('RO','BB','HB','FB','AI') NOT NULL,
  `adult` decimal(10,2) NOT NULL,
  `child` decimal(10,2) DEFAULT NULL,
  `season_id` int(10) unsigned DEFAULT NULL,
  `note` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_meal_delta` (`contract_id`,`from_plan`,`to_plan`),
  CONSTRAINT `fk_hr2_meal_delta_contract` FOREIGN KEY (`contract_id`) REFERENCES `hr2_contract` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_meal_delta` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr2_meal_delta` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_meal_price`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_meal_price` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` int(10) unsigned NOT NULL,
  `meal` enum('breakfast','lunch','dinner','other') NOT NULL,
  `name` varchar(80) DEFAULT NULL,
  `adult` decimal(10,2) NOT NULL,
  `child` decimal(10,2) DEFAULT NULL,
  `note` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_meal_price` (`contract_id`),
  CONSTRAINT `fk_hr2_meal_price_contract` FOREIGN KEY (`contract_id`) REFERENCES `hr2_contract` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_meal_price` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr2_meal_price` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_meta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_meta` (
  `k` varchar(40) NOT NULL,
  `v` varchar(400) DEFAULT NULL,
  PRIMARY KEY (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_meta` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr2_meta` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_offer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_offer` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` int(10) unsigned NOT NULL,
  `kind` enum('early_booking','long_stay','honeymoon','group_reduction','free_night','other') NOT NULL DEFAULT 'other',
  `name` varchar(160) NOT NULL,
  `book_from` date DEFAULT NULL,
  `book_to` date DEFAULT NULL,
  `stay_from` date DEFAULT NULL,
  `stay_to` date DEFAULT NULL,
  `min_nights` tinyint(3) unsigned DEFAULT NULL,
  `min_pax` smallint(5) unsigned DEFAULT NULL,
  `min_rooms` smallint(5) unsigned DEFAULT NULL,
  `value_pct` decimal(5,2) DEFAULT NULL,
  `value_amount` decimal(10,2) DEFAULT NULL,
  `free_nights` tinyint(3) unsigned DEFAULT NULL,
  `per` enum('person','room','booking','night') NOT NULL DEFAULT 'room',
  `combinable` tinyint(1) NOT NULL DEFAULT 0,
  `excludes_peak` tinyint(1) NOT NULL DEFAULT 0,
  `conditions` varchar(600) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_offer` (`contract_id`),
  CONSTRAINT `fk_hr2_offer_contract` FOREIGN KEY (`contract_id`) REFERENCES `hr2_contract` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_offer` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr2_offer` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_offer_choice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_offer_choice` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tour_id` varchar(50) NOT NULL,
  `hotel_acc` varchar(50) NOT NULL,
  `hotel_id` int(10) unsigned DEFAULT NULL,
  `choice` enum('published','offer') NOT NULL,
  `contract_id` int(10) unsigned DEFAULT NULL,
  `offer_name` varchar(255) DEFAULT NULL,
  `offer_kind` varchar(20) DEFAULT NULL,
  `note` varchar(300) DEFAULT NULL,
  `chosen_by` varchar(100) NOT NULL,
  `chosen_at` datetime NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `ended_at` datetime DEFAULT NULL,
  `ended_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_offer_choice` (`tour_id`,`hotel_acc`,`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_offer_choice` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr2_offer_choice` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_offer_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_offer_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tour_id` varchar(50) NOT NULL,
  `manual_id` varchar(50) DEFAULT NULL,
  `hotel_acc` varchar(50) NOT NULL,
  `hotel_id` int(10) unsigned DEFAULT NULL,
  `event` varchar(30) NOT NULL,
  `contract_id` int(10) unsigned DEFAULT NULL,
  `offer_name` varchar(255) DEFAULT NULL,
  `offer_kind` varchar(20) DEFAULT NULL,
  `nights` smallint(6) NOT NULL DEFAULT 0,
  `published_total` decimal(12,2) DEFAULT NULL,
  `used_total` decimal(12,2) DEFAULT NULL,
  `note` varchar(300) DEFAULT NULL,
  `by_user` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_offer_log` (`tour_id`,`hotel_acc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_offer_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr2_offer_log` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_payment_hold`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_payment_hold` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tour_id` varchar(50) NOT NULL,
  `manual_id` varchar(50) DEFAULT NULL,
  `hotel_acc` varchar(50) NOT NULL,
  `hotel_id` int(10) unsigned DEFAULT NULL,
  `hotel_name` varchar(255) DEFAULT NULL,
  `type` varchar(30) NOT NULL DEFAULT 'Hotel',
  `status` varchar(20) NOT NULL DEFAULT 'awaiting',
  `request_amount` decimal(12,2) DEFAULT NULL,
  `payload` text NOT NULL,
  `prepared_by` varchar(100) NOT NULL,
  `prepared_at` datetime NOT NULL,
  `decided_by` varchar(100) DEFAULT NULL,
  `decided_at` datetime DEFAULT NULL,
  `choice` varchar(20) DEFAULT NULL,
  `contract_id` int(10) unsigned DEFAULT NULL,
  `final_amount` decimal(12,2) DEFAULT NULL,
  `note` varchar(300) DEFAULT NULL,
  `payment_request_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_payment_hold_stay` (`tour_id`,`hotel_acc`),
  KEY `idx_hr2_payment_hold_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_payment_hold` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr2_payment_hold` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_payment_rule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_payment_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` int(10) unsigned NOT NULL,
  `applies` enum('all','credit','non_credit','fit','group','peak') NOT NULL DEFAULT 'all',
  `kind` enum('prepay','advance','interest','surcharge','credit_period','other') NOT NULL DEFAULT 'prepay',
  `days_before` smallint(6) DEFAULT NULL,
  `pct` decimal(5,2) DEFAULT NULL,
  `date_from` date DEFAULT NULL,
  `date_to` date DEFAULT NULL,
  `note` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_payment_rule` (`contract_id`),
  CONSTRAINT `fk_hr2_payment_rule_contract` FOREIGN KEY (`contract_id`) REFERENCES `hr2_contract` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_payment_rule` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr2_payment_rule` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_rate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_rate` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `season_id` int(10) unsigned NOT NULL,
  `room_type_id` int(10) unsigned NOT NULL,
  `meal_plan` enum('RO','BB','HB','FB','AI') NOT NULL,
  `sgl` decimal(10,2) DEFAULT NULL,
  `dbl` decimal(10,2) DEFAULT NULL,
  `tpl` decimal(10,2) DEFAULT NULL,
  `quad` decimal(10,2) DEFAULT NULL,
  `unit` decimal(10,2) DEFAULT NULL,
  `child_with_bed` decimal(10,2) DEFAULT NULL,
  `child_no_bed` decimal(10,2) DEFAULT NULL,
  `quality_flags` varchar(200) DEFAULT NULL,
  `note` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hr2_rate` (`season_id`,`room_type_id`,`meal_plan`),
  KEY `idx_hr2_rate_room` (`room_type_id`),
  CONSTRAINT `fk_hr2_rate_room_type` FOREIGN KEY (`room_type_id`) REFERENCES `hr2_room_type` (`id`),
  CONSTRAINT `fk_hr2_rate_season` FOREIGN KEY (`season_id`) REFERENCES `hr2_season` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_rate` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr2_rate` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_restriction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_restriction` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` int(10) unsigned NOT NULL,
  `kind` enum('min_stay','closed_to_arrival','closed_to_departure','meal_plan_required','stop_sale','other') NOT NULL,
  `date_from` date NOT NULL,
  `date_to` date NOT NULL,
  `value` varchar(60) DEFAULT NULL,
  `note` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_restriction` (`contract_id`,`date_from`,`date_to`),
  CONSTRAINT `fk_hr2_restriction_contract` FOREIGN KEY (`contract_id`) REFERENCES `hr2_contract` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_restriction` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr2_restriction` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_room_supplement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_room_supplement` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` int(10) unsigned NOT NULL,
  `room_type_id` int(10) unsigned NOT NULL,
  `season_id` int(10) unsigned DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `per` enum('room','person') NOT NULL DEFAULT 'room',
  `note` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_room_supplement` (`contract_id`,`room_type_id`),
  CONSTRAINT `fk_hr2_room_supplement_contract` FOREIGN KEY (`contract_id`) REFERENCES `hr2_contract` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_room_supplement` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr2_room_supplement` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_room_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_room_type` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hotel_id` int(10) unsigned NOT NULL,
  `name` varchar(160) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `legacy_room_type_id` int(11) DEFAULT NULL,
  `priced_as` enum('base','supplement') NOT NULL DEFAULT 'base',
  `base_room_type_id` int(10) unsigned DEFAULT NULL,
  `room_count` smallint(5) unsigned DEFAULT NULL,
  `size_sqm` decimal(6,1) DEFAULT NULL,
  `bed_config` varchar(120) DEFAULT NULL,
  `view` varchar(60) DEFAULT NULL,
  `max_adults` tinyint(3) unsigned NOT NULL DEFAULT 3,
  `max_children` tinyint(3) unsigned NOT NULL DEFAULT 2,
  `max_occupancy` tinyint(3) unsigned DEFAULT NULL,
  `extra_bed_allowed` tinyint(1) NOT NULL DEFAULT 1,
  `unit_pax_min` tinyint(3) unsigned DEFAULT NULL,
  `unit_pax_max` tinyint(3) unsigned DEFAULT NULL,
  `description` varchar(400) DEFAULT NULL,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hr2_room_type_hotel_name` (`hotel_id`,`name`),
  KEY `idx_hr2_room_type_legacy` (`legacy_room_type_id`),
  KEY `idx_hr2_room_type_base` (`base_room_type_id`),
  CONSTRAINT `fk_hr2_room_type_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hr2_hotel` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_room_type` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr2_room_type` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_season`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_season` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` int(10) unsigned NOT NULL,
  `name` varchar(120) DEFAULT NULL,
  `kind` enum('normal','peak','festive','low','other') NOT NULL DEFAULT 'normal',
  `min_stay` tinyint(3) unsigned DEFAULT NULL,
  `max_rooms` smallint(5) unsigned DEFAULT NULL,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `notes` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_season_contract` (`contract_id`,`sort_order`),
  CONSTRAINT `fk_hr2_season_contract` FOREIGN KEY (`contract_id`) REFERENCES `hr2_contract` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_season` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr2_season` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_season_date`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_season_date` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `season_id` int(10) unsigned NOT NULL,
  `date_from` date NOT NULL,
  `date_to` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_season_date` (`season_id`,`date_from`,`date_to`),
  CONSTRAINT `fk_hr2_season_date_season` FOREIGN KEY (`season_id`) REFERENCES `hr2_season` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_hr2_season_date` CHECK (`date_from` <= `date_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_season_date` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr2_season_date` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_sequence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_sequence` (
  `name` varchar(30) NOT NULL,
  `last_no` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_sequence` DISABLE KEYS */;
INSERT INTO `hr2_sequence` VALUES ('HTL',1);
/*!40000 ALTER TABLE `hr2_sequence` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_supplement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_supplement` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` int(10) unsigned NOT NULL,
  `type` enum('gala','peak','festival','extra_adult','early_checkin','late_checkout','other') NOT NULL DEFAULT 'other',
  `name` varchar(160) NOT NULL,
  `date_from` date DEFAULT NULL,
  `date_to` date DEFAULT NULL,
  `season_id` int(10) unsigned DEFAULT NULL,
  `applies_meal_plans` varchar(30) DEFAULT NULL,
  `room_type_id` int(10) unsigned DEFAULT NULL,
  `per` enum('person','adult','child','room','night','booking') NOT NULL DEFAULT 'person',
  `amount` decimal(10,2) DEFAULT NULL,
  `amount_sgl` decimal(10,2) DEFAULT NULL,
  `amount_dbl` decimal(10,2) DEFAULT NULL,
  `amount_tpl` decimal(10,2) DEFAULT NULL,
  `child_amount` decimal(10,2) DEFAULT NULL,
  `child_pct` decimal(5,2) DEFAULT NULL,
  `pct_of_rate` decimal(5,2) DEFAULT NULL,
  `compulsory` tinyint(1) NOT NULL DEFAULT 1,
  `note` varchar(400) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_supplement_contract` (`contract_id`,`date_from`,`date_to`),
  CONSTRAINT `fk_hr2_supplement_contract` FOREIGN KEY (`contract_id`) REFERENCES `hr2_contract` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_supplement` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr2_supplement` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_tour_hotel_cost`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_tour_hotel_cost` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tour_id` varchar(50) NOT NULL,
  `manual_id` varchar(50) DEFAULT NULL,
  `quotation_row_id` int(11) NOT NULL,
  `hotel_acc` varchar(50) NOT NULL,
  `hotel_id` int(10) unsigned DEFAULT NULL,
  `tour_date` date NOT NULL,
  `room_type_legacy` int(11) DEFAULT NULL,
  `room_type_name` varchar(160) DEFAULT NULL,
  `meal_plan` varchar(2) DEFAULT NULL,
  `market` varchar(10) DEFAULT NULL,
  `sgl_count` smallint(6) NOT NULL DEFAULT 0,
  `dbl_count` smallint(6) NOT NULL DEFAULT 0,
  `tpl_count` smallint(6) NOT NULL DEFAULT 0,
  `quote_sgl` decimal(10,2) DEFAULT NULL,
  `quote_dbl` decimal(10,2) DEFAULT NULL,
  `quote_tpl` decimal(10,2) DEFAULT NULL,
  `pay_sgl` decimal(10,2) DEFAULT NULL,
  `pay_dbl` decimal(10,2) DEFAULT NULL,
  `pay_tpl` decimal(10,2) DEFAULT NULL,
  `pay_supplements` decimal(10,2) NOT NULL DEFAULT 0.00,
  `quote_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `pay_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `source` enum('adhoc','contracted','quoted','none') NOT NULL DEFAULT 'none',
  `plan_id` int(10) unsigned DEFAULT NULL,
  `plan_name` varchar(160) DEFAULT NULL,
  `season_name` varchar(120) DEFAULT NULL,
  `derived` varchar(60) DEFAULT NULL,
  `flags_json` text DEFAULT NULL,
  `check_note` varchar(300) DEFAULT NULL,
  `computed_at` datetime NOT NULL,
  `computed_by` varchar(100) DEFAULT NULL,
  `pub_sgl` decimal(10,2) DEFAULT NULL,
  `pub_dbl` decimal(10,2) DEFAULT NULL,
  `pub_tpl` decimal(10,2) DEFAULT NULL,
  `pub_supplements` decimal(10,2) DEFAULT NULL,
  `pub_total` decimal(12,2) DEFAULT NULL,
  `pub_plan_id` int(10) unsigned DEFAULT NULL,
  `pub_plan_name` varchar(160) DEFAULT NULL,
  `basis` varchar(20) DEFAULT NULL,
  `offer_plan_id` int(10) unsigned DEFAULT NULL,
  `offer_kind` varchar(20) DEFAULT NULL,
  `offer_name` varchar(255) DEFAULT NULL,
  `options_json` text DEFAULT NULL,
  `choice_needed` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hr2_tour_hotel_cost_row` (`quotation_row_id`),
  KEY `idx_hr2_tour_hotel_cost_tour` (`tour_id`,`hotel_acc`),
  KEY `idx_hr2_tour_hotel_cost_hotel` (`hotel_id`,`tour_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_tour_hotel_cost` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr2_tour_hotel_cost` ENABLE KEYS */;
DROP TABLE IF EXISTS `hr2_work_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr2_work_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `item_key` varchar(190) NOT NULL,
  `kind` varchar(40) NOT NULL,
  `level` enum('red','amber','blue') NOT NULL DEFAULT 'amber',
  `title` varchar(255) NOT NULL,
  `detail` text DEFAULT NULL,
  `hotel_id` int(10) unsigned DEFAULT NULL,
  `hotel_acc` varchar(50) DEFAULT NULL,
  `hotel_name` varchar(190) DEFAULT NULL,
  `tour_id` varchar(50) DEFAULT NULL,
  `manual_id` varchar(50) DEFAULT NULL,
  `plan_id` int(10) unsigned DEFAULT NULL,
  `ref_date` date DEFAULT NULL,
  `ref_date_to` date DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `data_json` text DEFAULT NULL,
  `source` enum('auto','manual') NOT NULL DEFAULT 'auto',
  `status` enum('open','done','ignored') NOT NULL DEFAULT 'open',
  `note` text DEFAULT NULL,
  `seen_count` int(10) unsigned NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `updated_at` datetime NOT NULL,
  `last_seen_at` datetime DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `resolved_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hr2_work_key` (`item_key`),
  KEY `ix_hr2_work_status_kind` (`status`,`kind`),
  KEY `ix_hr2_work_hotel` (`hotel_id`),
  KEY `ix_hr2_work_tour` (`tour_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `hr2_work_item` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr2_work_item` ENABLE KEYS */;
DROP TABLE IF EXISTS `inquiry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inquiry` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `message` text DEFAULT NULL,
  `record_time` datetime DEFAULT NULL,
  `reply_flag` int(11) DEFAULT 0,
  `agent_name` varchar(255) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `roomsgl` int(11) DEFAULT NULL,
  `roomdbl` int(11) DEFAULT NULL,
  `roomtpl` int(11) DEFAULT NULL,
  `is_accepted` int(11) DEFAULT 0,
  `is_created` int(11) DEFAULT 0,
  `inquiry_code` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `inquiry` DISABLE KEYS */;
/*!40000 ALTER TABLE `inquiry` ENABLE KEYS */;
DROP TABLE IF EXISTS `inquiry_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inquiry_activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inquiry_id` int(11) DEFAULT NULL,
  `inquiry_row` int(11) DEFAULT NULL,
  `inquiry_date` date DEFAULT NULL,
  `name` varchar(500) DEFAULT NULL,
  `code` varchar(100) DEFAULT NULL,
  `ref_no` int(11) DEFAULT 0,
  `place_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_inquiry_id` (`inquiry_id`) USING BTREE,
  KEY `idx_inquiry_row` (`inquiry_row`) USING BTREE,
  KEY `idx_inquiry_date` (`inquiry_date`) USING BTREE,
  KEY `idx_ref_no` (`ref_no`) USING BTREE,
  KEY `idx_place_name` (`place_name`(100)) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `inquiry_activities` DISABLE KEYS */;
/*!40000 ALTER TABLE `inquiry_activities` ENABLE KEYS */;
DROP TABLE IF EXISTS `inquiry_consignee`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inquiry_consignee` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inquiry_id` varchar(255) DEFAULT NULL,
  `consignee` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `k_code` varchar(255) DEFAULT NULL,
  `mobile` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `agent_ref` varchar(255) DEFAULT NULL,
  `transport_agent` varchar(255) DEFAULT NULL,
  `transport_agent_name` varchar(255) DEFAULT NULL,
  `guide` varchar(255) DEFAULT NULL,
  `guide_name` varchar(255) DEFAULT NULL,
  `vehicle` varchar(255) DEFAULT NULL,
  `vehicle_name` varchar(255) DEFAULT NULL,
  `vehicle_rate` decimal(10,2) DEFAULT 0.00,
  `passenger_count` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `create_user` varchar(255) DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `manual_id` varchar(255) DEFAULT NULL,
  `agent` varchar(255) DEFAULT NULL,
  `agent_name` varchar(255) DEFAULT NULL,
  `arrival_flight` varchar(255) DEFAULT NULL,
  `departure_flight` varchar(255) DEFAULT NULL,
  `arrival_flight_time` time DEFAULT NULL,
  `departure_flight_time` time DEFAULT NULL,
  `hotel_only` int(11) DEFAULT 0,
  `transport_only` int(11) DEFAULT 0,
  `finalize` int(11) DEFAULT 0,
  `finalize_txt` int(11) DEFAULT 0,
  `is_template` int(11) DEFAULT 0,
  `ref_no` int(11) DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_inquiry_id` (`inquiry_id`(250)) USING BTREE,
  KEY `idx_start_date` (`start_date`) USING BTREE,
  KEY `idx_end_date` (`end_date`) USING BTREE,
  KEY `idx_manual_id` (`manual_id`(250)) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `inquiry_consignee` DISABLE KEYS */;
/*!40000 ALTER TABLE `inquiry_consignee` ENABLE KEYS */;
DROP TABLE IF EXISTS `inquiry_itinerary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inquiry_itinerary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inquiry_id` int(11) DEFAULT NULL,
  `inquiry_row` int(11) DEFAULT NULL,
  `inquiry_date` date DEFAULT NULL,
  `order_id` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `area` varchar(255) DEFAULT NULL,
  `bf` tinyint(1) DEFAULT 0,
  `lunch` tinyint(1) DEFAULT 0,
  `dinner` tinyint(1) DEFAULT 0,
  `insert_date` datetime DEFAULT current_timestamp(),
  `ref_no` int(11) DEFAULT 0,
  `loc_ref` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_inquiry_id` (`inquiry_id`) USING BTREE,
  KEY `idx_inquiry_row` (`inquiry_row`) USING BTREE,
  KEY `idx_inquiry_date` (`inquiry_date`) USING BTREE,
  KEY `idx_order_id` (`order_id`) USING BTREE,
  KEY `idx_ref_no` (`ref_no`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `inquiry_itinerary` DISABLE KEYS */;
/*!40000 ALTER TABLE `inquiry_itinerary` ENABLE KEYS */;
DROP TABLE IF EXISTS `inquiry_passenger_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inquiry_passenger_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pass_name` varchar(255) DEFAULT NULL,
  `inquiry_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_inquiry_id` (`inquiry_id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `inquiry_passenger_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `inquiry_passenger_details` ENABLE KEYS */;
DROP TABLE IF EXISTS `inquiry_quotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inquiry_quotations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inquiry_id` varchar(255) DEFAULT NULL,
  `quotation_day` decimal(10,1) DEFAULT NULL,
  `hotel_code` varchar(255) DEFAULT NULL,
  `meal_code` varchar(255) DEFAULT NULL,
  `hotel_acc` varchar(255) DEFAULT NULL,
  `hotel_name` varchar(255) DEFAULT NULL,
  `room_type` varchar(255) DEFAULT NULL,
  `room_type_id` int(11) DEFAULT NULL,
  `tour_date` date DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_time` datetime DEFAULT NULL,
  `created_user` varchar(255) DEFAULT NULL,
  `distance_type` int(11) DEFAULT NULL,
  `det_flag` int(11) DEFAULT 0,
  `transfer_code` varchar(255) DEFAULT NULL,
  `transfer` varchar(255) DEFAULT NULL,
  `distance` double DEFAULT NULL,
  `from_location` varchar(255) DEFAULT NULL,
  `to_location` varchar(255) DEFAULT NULL,
  `gdistance` double DEFAULT NULL,
  `added_by` varchar(255) DEFAULT NULL,
  `added_time` datetime DEFAULT NULL,
  `trans_flag` int(11) DEFAULT 0,
  `extra_kilo` double DEFAULT NULL,
  `is_sub` int(11) DEFAULT 0,
  `saved` int(11) DEFAULT 0,
  `ref_no` int(11) DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_inquiry_id` (`inquiry_id`(250)) USING BTREE,
  KEY `idx_tour_date` (`tour_date`) USING BTREE,
  KEY `idx_saved` (`saved`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `inquiry_quotations` DISABLE KEYS */;
/*!40000 ALTER TABLE `inquiry_quotations` ENABLE KEYS */;
DROP TABLE IF EXISTS `inquiry_sub_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inquiry_sub_locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inquiry_id` int(11) DEFAULT NULL,
  `date_id` int(11) DEFAULT NULL,
  `inquiry_row` int(11) DEFAULT NULL,
  `from` varchar(255) DEFAULT NULL,
  `to` varchar(255) DEFAULT NULL,
  `km` decimal(10,2) DEFAULT NULL,
  `extra_km` decimal(10,2) DEFAULT NULL,
  `estimate_km` decimal(10,2) DEFAULT NULL,
  `ref_no` int(11) DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_inquiry_id` (`inquiry_id`) USING BTREE,
  KEY `idx_date_id` (`date_id`) USING BTREE,
  KEY `idx_ref_no` (`ref_no`) USING BTREE,
  KEY `idx_tour_row` (`inquiry_row`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `inquiry_sub_locations` DISABLE KEYS */;
/*!40000 ALTER TABLE `inquiry_sub_locations` ENABLE KEYS */;
DROP TABLE IF EXISTS `inquiry_timeline`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inquiry_timeline` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inquiry_id` int(11) DEFAULT NULL,
  `inquiry_day` int(11) DEFAULT NULL,
  `inquiry_date` varchar(255) DEFAULT NULL,
  `from_location` varchar(255) DEFAULT NULL,
  `to_location` varchar(255) DEFAULT NULL,
  `hotel_acc` int(11) DEFAULT NULL,
  `hotel_name` varchar(255) DEFAULT NULL,
  `room_type` int(11) DEFAULT NULL,
  `meal_code` varchar(50) DEFAULT NULL,
  `gdistance` decimal(10,2) DEFAULT NULL,
  `distance` decimal(10,2) DEFAULT NULL,
  `extra_kilo` decimal(10,2) DEFAULT NULL,
  `transfer_code` int(11) DEFAULT NULL,
  `created_time` datetime DEFAULT current_timestamp(),
  `created_user` int(11) DEFAULT NULL,
  `saved` tinyint(1) DEFAULT 0,
  `is_sub` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_inquiry_id` (`inquiry_id`) USING BTREE,
  KEY `idx_tour_date` (`inquiry_date`) USING BTREE,
  KEY `idx_inquiry_day` (`inquiry_day`) USING BTREE,
  KEY `idx_hotel_acc` (`hotel_acc`) USING BTREE,
  KEY `idx_saved` (`saved`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `inquiry_timeline` DISABLE KEYS */;
/*!40000 ALTER TABLE `inquiry_timeline` ENABLE KEYS */;
DROP TABLE IF EXISTS `international_airport`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `international_airport` (
  `IATA` varchar(100) NOT NULL,
  `ICAO` varchar(100) NOT NULL,
  `airport_name` varchar(100) NOT NULL,
  `country` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `Information` varchar(500) NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `airport_name_j` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `international_airport` DISABLE KEYS */;
/*!40000 ALTER TABLE `international_airport` ENABLE KEYS */;
DROP TABLE IF EXISTS `inv_alert_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_alert_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tour_id` varchar(255) DEFAULT NULL,
  `manual_id` varchar(255) DEFAULT NULL,
  `inv_no` varchar(255) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `action_user` varchar(255) DEFAULT NULL,
  `action_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `inv_alert_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `inv_alert_log` ENABLE KEYS */;
DROP TABLE IF EXISTS `inv_description`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_description` (
  `id` smallint(6) NOT NULL AUTO_INCREMENT,
  `description` varchar(60) NOT NULL,
  `del` int(11) DEFAULT 0,
  `user` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `inv_description` DISABLE KEYS */;
/*!40000 ALTER TABLE `inv_description` ENABLE KEYS */;
DROP TABLE IF EXISTS `inventory_stock_view`;
/*!50001 DROP VIEW IF EXISTS `inventory_stock_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `inventory_stock_view` AS SELECT
 1 AS `Stock_Card`,
  1 AS `item_description`,
  1 AS `reorder_level`,
  1 AS `purchase_price`,
  1 AS `current_balance`,
  1 AS `avg_cost`,
  1 AS `last_cost`,
  1 AS `stock_status` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `invoice_cat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_cat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `j_name` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `description` varchar(300) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `invoice_cat` DISABLE KEYS */;
/*!40000 ALTER TABLE `invoice_cat` ENABLE KEYS */;
DROP TABLE IF EXISTS `invoice_no`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_no` (
  `Stock` int(11) NOT NULL DEFAULT 0,
  `No` int(11) NOT NULL DEFAULT 0,
  `Recipt_No` int(11) NOT NULL DEFAULT 0,
  `Gatepass` int(11) NOT NULL DEFAULT 0,
  `Purchase` int(11) NOT NULL DEFAULT 0,
  `Invoice_2` int(11) NOT NULL DEFAULT 0,
  `Invoice_3` int(11) NOT NULL DEFAULT 0,
  `start_t` datetime DEFAULT NULL,
  `end_t` datetime DEFAULT NULL,
  `DealerC` int(11) NOT NULL DEFAULT 0,
  `Sales_return` int(11) NOT NULL DEFAULT 0,
  `V_Num` int(10) unsigned NOT NULL DEFAULT 0,
  `Pay_slip` int(11) NOT NULL DEFAULT 0,
  `Aut` varchar(100) NOT NULL DEFAULT '0',
  `Accs` smallint(6) NOT NULL DEFAULT 0,
  `dv_id` int(10) unsigned NOT NULL DEFAULT 0,
  `w_id` int(10) unsigned NOT NULL DEFAULT 0,
  `Grns` int(10) unsigned NOT NULL DEFAULT 0,
  `slock` int(10) unsigned NOT NULL DEFAULT 0,
  `G_id` int(10) unsigned NOT NULL DEFAULT 0,
  `sr` int(11) NOT NULL DEFAULT 0,
  `day` smallint(6) NOT NULL DEFAULT 0,
  `job_card` int(11) NOT NULL DEFAULT 0,
  `ins_ref` int(11) NOT NULL DEFAULT 0,
  `Job_invoice` int(11) NOT NULL DEFAULT 0,
  `Job_reg` int(11) NOT NULL DEFAULT 0,
  `Gatepass_s` int(11) NOT NULL DEFAULT 0,
  `repair_inv` int(11) NOT NULL DEFAULT 0,
  `Month` smallint(6) NOT NULL DEFAULT 0,
  `yy` smallint(6) NOT NULL DEFAULT 0,
  `srvc_no` int(11) NOT NULL DEFAULT 0,
  `pos_receipt_no` int(11) DEFAULT NULL,
  `shift_id` bigint(20) DEFAULT NULL,
  `external_cus` bigint(20) DEFAULT NULL,
  `check_out_id` int(11) DEFAULT NULL,
  `LS` int(11) DEFAULT NULL,
  `STC` int(11) DEFAULT NULL,
  `TMP` int(11) DEFAULT NULL,
  `STS` int(11) DEFAULT NULL,
  `INCT` int(11) DEFAULT NULL,
  `Item_Token` int(11) DEFAULT NULL,
  `Item_Req` int(11) DEFAULT NULL,
  `Purchase_Token` int(11) DEFAULT 0,
  `Purchases` int(11) DEFAULT 0,
  `app_no` int(11) DEFAULT NULL,
  `sms_bulk_id` int(11) DEFAULT NULL,
  `notice_bulk_id` int(11) DEFAULT NULL,
  `Dispose_Token` int(11) DEFAULT NULL,
  `Disposes` int(11) DEFAULT NULL,
  `Dispose` int(11) DEFAULT NULL,
  `promoted_year` int(11) DEFAULT NULL,
  `fx_Dispose_Token` int(11) DEFAULT NULL,
  `fx_Disposes` int(11) DEFAULT NULL,
  `inventory_receipt` int(11) DEFAULT NULL,
  `Pro_purchase_no` int(11) NOT NULL,
  `supplier_return` int(11) NOT NULL,
  `sup_return_token` int(11) NOT NULL,
  `voucher` int(11) NOT NULL,
  `profotma_no` int(11) DEFAULT NULL,
  `Tour_No` int(11) DEFAULT 0,
  `acc_id` int(11) DEFAULT 0,
  `JE_inv` int(11) DEFAULT 0,
  `tour_manual_id` int(11) DEFAULT NULL,
  `booking_no` int(11) DEFAULT 0,
  `customerNo` int(11) DEFAULT 0,
  `passenger_no` int(11) DEFAULT 0,
  `debtor_receipt_no` int(11) DEFAULT 0,
  `visa_no` int(11) DEFAULT 0,
  `pass_id` int(11) DEFAULT 0,
  `entertainment_expenses_inv` int(11) NOT NULL DEFAULT 0,
  `inquiry_no` int(11) DEFAULT 0,
  `hotel_code` int(11) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `invoice_no` DISABLE KEYS */;
/*!40000 ALTER TABLE `invoice_no` ENABLE KEYS */;
DROP TABLE IF EXISTS `itinenary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `itinenary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `arrival_airport` varchar(50) NOT NULL,
  `arrival_time` time NOT NULL,
  `real_arrival_time` varchar(50) NOT NULL DEFAULT '',
  `boarding_date` date NOT NULL,
  `carrier` varchar(250) NOT NULL,
  `carrier_class` varchar(5) NOT NULL,
  `departure_airport` varchar(100) NOT NULL,
  `departure_time` time NOT NULL,
  `flight_no` varchar(50) NOT NULL,
  `flight_way` varchar(50) NOT NULL,
  `modal_tour_id` int(11) NOT NULL DEFAULT 0,
  `note` varchar(100) NOT NULL,
  `ticket_option` varchar(25) NOT NULL,
  `trip_no` varchar(10) NOT NULL,
  `departure_date` date DEFAULT NULL,
  `arrival_date` date DEFAULT NULL,
  `line_no` int(11) NOT NULL,
  `status` varchar(10) NOT NULL DEFAULT '',
  `farebase` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `itinenary` DISABLE KEYS */;
/*!40000 ALTER TABLE `itinenary` ENABLE KEYS */;
DROP TABLE IF EXISTS `job_role_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_role_assignments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'FK to user_acc_tbl.id',
  `job_role_id` int(10) unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `assigned_at` datetime DEFAULT current_timestamp(),
  `assigned_by` varchar(100) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `del` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_job_role_id` (`job_role_id`),
  KEY `idx_user_active` (`user_id`,`del`),
  KEY `idx_dates` (`start_date`,`end_date`),
  KEY `idx_del` (`del`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `job_role_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_role_assignments` ENABLE KEYS */;
DROP TABLE IF EXISTS `job_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `job_role` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `del` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_job_role` (`job_role`),
  KEY `idx_del` (`del`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `job_roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_roles` ENABLE KEYS */;
DROP TABLE IF EXISTS `log_sheet_mile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `log_sheet_mile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tour_id` varchar(255) DEFAULT NULL,
  `transport_agent` varchar(255) DEFAULT NULL,
  `log_sheet_mileage` decimal(10,2) unsigned zerofill DEFAULT 00000000.00,
  `previous_mileage` decimal(10,2) DEFAULT NULL,
  `estimate_mileage` decimal(10,2) DEFAULT 0.00,
  `vehicle_rate` decimal(10,2) DEFAULT 0.00,
  `per_day_rate` decimal(10,2) DEFAULT 0.00,
  `no_of_dates` int(11) DEFAULT 0,
  `is_override` tinyint(1) NOT NULL DEFAULT 0,
  `overridden_by` varchar(255) DEFAULT NULL,
  `login` varchar(255) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `log_sheet_mile` DISABLE KEYS */;
/*!40000 ALTER TABLE `log_sheet_mile` ENABLE KEYS */;
DROP TABLE IF EXISTS `menu_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `menu_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(100) NOT NULL,
  `user` int(11) NOT NULL,
  `del` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `menu_category` DISABLE KEYS */;
INSERT INTO `menu_category` VALUES (1,'Registrations',33740,1,4);
INSERT INTO `menu_category` VALUES (2,'Settings',33740,0,20);
INSERT INTO `menu_category` VALUES (4,'Master Files',33740,0,1);
INSERT INTO `menu_category` VALUES (10,'Staff Directory',33740,0,7);
INSERT INTO `menu_category` VALUES (13,'Accounts',33740,0,3);
INSERT INTO `menu_category` VALUES (20,'tsst 1',33740,1,1);
INSERT INTO `menu_category` VALUES (21,'Tour',33740,0,2);
INSERT INTO `menu_category` VALUES (22,'Payments',33740,0,0);
INSERT INTO `menu_category` VALUES (23,'Accounts Reports',33740,0,4);
INSERT INTO `menu_category` VALUES (28,'Transport & Guide Services',1,0,1);
/*!40000 ALTER TABLE `menu_category` ENABLE KEYS */;
DROP TABLE IF EXISTS `menu_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `menu_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cat_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `user` int(11) NOT NULL,
  `del` int(11) NOT NULL,
  `url` varchar(100) NOT NULL,
  `order_id` int(11) NOT NULL,
  `show_in_menu_bar` int(11) DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `menu_list` DISABLE KEYS */;
INSERT INTO `menu_list` VALUES (1,2,'Group Profiles',33740,0,'settings/group_profile',3,0);
INSERT INTO `menu_list` VALUES (2,2,'Menu Categories',33740,0,'settings/menu_category',4,0);
INSERT INTO `menu_list` VALUES (3,2,'Menu Lists',33740,0,'settings/menu_list',5,0);
INSERT INTO `menu_list` VALUES (4,2,'Group Privileges',33740,0,'settings/group_privilege/0',6,0);
INSERT INTO `menu_list` VALUES (5,2,'User Accounts',33740,0,'settings/user_account',7,0);
INSERT INTO `menu_list` VALUES (29,4,'Supplier',33740,0,'master_files/supplier',1,0);
INSERT INTO `menu_list` VALUES (32,4,'Supplier Report',33740,0,'master_files/supplier/all',2,0);
INSERT INTO `menu_list` VALUES (36,2,'Staff Registration',33740,0,'staff/registration',1,0);
INSERT INTO `menu_list` VALUES (37,2,'Staff Directory',33740,0,'staff/directory',2,0);
INSERT INTO `menu_list` VALUES (49,13,'Manage Accounts',33740,0,'accounts',10,0);
INSERT INTO `menu_list` VALUES (51,23,'Balance Sheet',33740,0,'accounts/balance_sheet',6,0);
INSERT INTO `menu_list` VALUES (56,13,'Creditor Payment',33740,0,'accounts/creditor_payment',25,0);
INSERT INTO `menu_list` VALUES (66,4,'Place to Visit',33740,0,'master_files/entrance_fee',6,0);
INSERT INTO `menu_list` VALUES (67,4,'Hotel Rate',33740,0,'master_files/hotel_rate',4,0);
INSERT INTO `menu_list` VALUES (68,4,'Room Type',33740,0,'master_files/room_type',3,0);
INSERT INTO `menu_list` VALUES (69,13,'Crediter',33740,1,'accounts/creditor_payment',0,0);
INSERT INTO `menu_list` VALUES (70,4,'Hotel Rate Report',33740,0,'master_files/hotel_rate/all',5,0);
INSERT INTO `menu_list` VALUES (71,21,'Tour Quotation',33740,0,'tour/tour',1,0);
INSERT INTO `menu_list` VALUES (72,21,'Tour Costing',33740,0,'tour/tour_costing',4,0);
INSERT INTO `menu_list` VALUES (73,4,'Routing Registry',33740,0,'master_files/transfer_milages',7,0);
INSERT INTO `menu_list` VALUES (74,21,'Quotation Report',33740,0,'tour/tour_details',2,0);
INSERT INTO `menu_list` VALUES (75,21,'Whole Tour Details',33740,1,'tour/tour_details/findAllDetails',0,0);
INSERT INTO `menu_list` VALUES (76,21,'Tour Management',33740,0,'tour/booking_confirm',3,0);
INSERT INTO `menu_list` VALUES (77,4,'Vehicle',33740,0,'master_files/vehicle',8,0);
INSERT INTO `menu_list` VALUES (78,13,'Debtor Payment',33740,0,'accounts/debtor_payment',30,0);
INSERT INTO `menu_list` VALUES (79,21,'Tour Inquiry',33740,0,'inquiries',0,0);
INSERT INTO `menu_list` VALUES (80,21,'Tour Copy',33740,0,'tour/tour_copy',10,0);
INSERT INTO `menu_list` VALUES (81,13,'Journal Entry',33740,0,'accounts/journal_entry',12,0);
INSERT INTO `menu_list` VALUES (82,13,'Voucher Payments',33740,0,'accounts/voucher_payments/0',35,0);
INSERT INTO `menu_list` VALUES (83,13,'Payment Authentication',33740,1,'accounts/voucher_auth',36,0);
INSERT INTO `menu_list` VALUES (84,13,'Creditor Invoice',33740,0,'accounts/creditor_invoice',26,0);
INSERT INTO `menu_list` VALUES (85,4,'Conginee',33740,0,'master_files/customer',8,0);
INSERT INTO `menu_list` VALUES (86,13,'Debtor Invoice',33740,0,'accounts/debtor_invoice',31,0);
INSERT INTO `menu_list` VALUES (87,2,'Backup Database',33740,0,'settings/backup_database',0,0);
INSERT INTO `menu_list` VALUES (88,23,'Liability Report',33740,0,'accounts/liability',1,0);
INSERT INTO `menu_list` VALUES (89,23,'Advance Payments Report',33740,0,'accounts/advance_payment/report',12,0);
INSERT INTO `menu_list` VALUES (90,13,'Advance Payments',33740,0,'accounts/advance_payment',20,0);
INSERT INTO `menu_list` VALUES (91,23,'Creditor Invoice Report',33740,0,'accounts/creditor_invoice/report',15,0);
INSERT INTO `menu_list` VALUES (92,23,'Debtor Invoice Report',33740,0,'accounts/debtor_invoice/report',18,0);
INSERT INTO `menu_list` VALUES (93,4,'Extra Kilometers',33740,0,'master_files/extra_kilometers',10,0);
INSERT INTO `menu_list` VALUES (94,23,'Expenses Chart',33740,0,'accounts/expenses_chart',3,0);
INSERT INTO `menu_list` VALUES (95,23,'Operational PNL',33740,0,'accounts/creditor_invoice/expenses_report',9,0);
INSERT INTO `menu_list` VALUES (96,23,'Voucher Authentication',33740,0,'accounts/creditor_invoice/voucher_authentication',21,0);
INSERT INTO `menu_list` VALUES (97,13,'Voucher Authentication Report',33740,0,'accounts/creditor_invoice/voucher_authentication',28,0);
INSERT INTO `menu_list` VALUES (98,21,'Tour Calender',33740,0,'tour/calendar',5,0);
INSERT INTO `menu_list` VALUES (99,21,'Calendar',33740,1,'tour/calendar',0,0);
INSERT INTO `menu_list` VALUES (100,23,'Trial Balance Report',33740,0,'accounts/trial_balance',0,0);
INSERT INTO `menu_list` VALUES (101,23,'Cash Book',33740,0,'accounts/cash_book',0,0);
INSERT INTO `menu_list` VALUES (102,23,'Journal Entry Report',33740,0,'accounts/journal_entry/report',0,0);
INSERT INTO `menu_list` VALUES (103,23,'Profit and Loss Report',33740,0,'accounts/profit_loss',0,0);
INSERT INTO `menu_list` VALUES (104,23,'Age Analysis Report',33740,0,'accounts/age_analysis',0,0);
INSERT INTO `menu_list` VALUES (105,23,'Creditor Age Analysis',33740,0,'accounts/age_analysis/creditor',0,0);
INSERT INTO `menu_list` VALUES (106,21,'New Quotation',33740,0,'tour/quotation',0,0);
INSERT INTO `menu_list` VALUES (107,2,'Manual Update',33740,0,'settings/manual_update',0,0);
INSERT INTO `menu_list` VALUES (108,23,'Accounts Filtering',33740,0,'accounts/account_filter',0,0);
INSERT INTO `menu_list` VALUES (109,23,'Bank Statement',33740,0,'accounts/bank_statement',0,0);
INSERT INTO `menu_list` VALUES (110,21,'Tour Dashboard',33740,0,'tour/tour_dashboard',0,0);
INSERT INTO `menu_list` VALUES (222,21,'Tour Inquiry',1,0,'tour/intake',26,0);
INSERT INTO `menu_list` VALUES (223,28,'Transport Rates',1,0,'master_files/transport_rates',1,0);
INSERT INTO `menu_list` VALUES (224,28,'Mileage & Allowances',1,0,'master_files/mileage_allowances',2,0);
INSERT INTO `menu_list` VALUES (225,21,'Hotel Bookings',1,0,'tour/hotel_bookings',27,0);
INSERT INTO `menu_list` VALUES (226,21,'Transport Operations',1,0,'tour/transport_ops',28,0);
INSERT INTO `menu_list` VALUES (227,21,'Tour Board',1,0,'tour/board',29,0);
INSERT INTO `menu_list` VALUES (228,4,'Excursion Rates',1,0,'master_files/excursion_rates',26,0);
INSERT INTO `menu_list` VALUES (229,21,'Pricing',1,0,'tour/pricing',30,0);
INSERT INTO `menu_list` VALUES (230,21,'Mileage override',1,0,'tour/intake/mileage',31,1);
INSERT INTO `menu_list` VALUES (231,21,'Tour Desk Admin',1,0,'tour/manage',32,0);
INSERT INTO `menu_list` VALUES (232,28,'Crew Roles & Quotation Costing',1,0,'master_files/crew_bata',4,0);
INSERT INTO `menu_list` VALUES (233,21,'Crew replacement',1,0,'tour/transport_ops/crew_change',33,1);
INSERT INTO `menu_list` VALUES (234,21,'Driver advance',1,0,'tour/transport_ops/driver_advance',34,1);
INSERT INTO `menu_list` VALUES (235,4,'Tour Photos',1,0,'master_files/tour_photos',28,0);
INSERT INTO `menu_list` VALUES (236,4,'Free Visits',1,0,'master_files/free_visits',29,0);
INSERT INTO `menu_list` VALUES (237,4,'Paid Activities & Services',1,0,'master_files/paid_services',30,0);
INSERT INTO `menu_list` VALUES (238,28,'Drivers & Guides',1,0,'master_files/crew_people',3,0);
INSERT INTO `menu_list` VALUES (239,21,'Ticket Stock',1,0,'tour/ticket_stock',35,0);
INSERT INTO `menu_list` VALUES (240,4,'Place to Visit / Entrance Fee Master',1,0,'master_files/entrance_master',32,0);
INSERT INTO `menu_list` VALUES (241,4,'Meal Master',1,0,'master_files/meal_master',33,0);
INSERT INTO `menu_list` VALUES (242,4,'Tactical / Early Bird rate selection',1,0,'master_files/offer_selection',34,1);
INSERT INTO `menu_list` VALUES (243,4,'Hotel Rates (new)',1,0,'master_files/hotel_rate_v2',35,0);
INSERT INTO `menu_list` VALUES (244,4,'Hotel Rate Approval',1,0,'master_files/hotel_rate_approval',36,0);
INSERT INTO `menu_list` VALUES (245,4,'Hotel Incomplete Work',1,0,'master_files/hotel_work',37,0);
INSERT INTO `menu_list` VALUES (246,28,'Driver & Guide bank details',1,0,'master_files/crew_people/bank',5,1);
/*!40000 ALTER TABLE `menu_list` ENABLE KEYS */;
DROP TABLE IF EXISTS `menu_list_copy1`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `menu_list_copy1` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cat_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `user` int(11) NOT NULL,
  `del` int(11) NOT NULL,
  `url` varchar(100) NOT NULL,
  `order_id` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `menu_list_copy1` DISABLE KEYS */;
/*!40000 ALTER TABLE `menu_list_copy1` ENABLE KEYS */;
DROP TABLE IF EXISTS `other_invoice_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `other_invoice_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent` varchar(200) NOT NULL,
  `income_type` varchar(250) NOT NULL,
  `date` date NOT NULL,
  `description` varchar(250) NOT NULL,
  `invoice_no` varchar(255) NOT NULL,
  `cancel` int(11) NOT NULL DEFAULT 0,
  `cancel_user` varchar(150) NOT NULL,
  `cancel_date` date NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `created_by` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `amount_jpy` decimal(15,2) DEFAULT NULL COMMENT 'Amount in Japanese Yen',
  `conversion_rate` decimal(10,4) DEFAULT NULL COMMENT 'JPY to LKR conversion rate',
  `amount_lkr` decimal(15,2) DEFAULT NULL COMMENT 'Calculated amount in LKR',
  `is_staff_invoice` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=Regular Invoice, 1=Staff Invoice',
  `pay_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=Unpaid, 1=Paid',
  `payment_by` varchar(100) DEFAULT NULL COMMENT 'Username who collected the payment',
  `payment_datetime` datetime DEFAULT NULL COMMENT 'Date and time the payment was collected',
  `pay_jpy_amount` decimal(15,2) DEFAULT NULL COMMENT 'JPY amount paid by service provider',
  `pay_rate` decimal(10,4) DEFAULT NULL COMMENT 'JPY to LKR rate used at time of payment',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `other_invoice_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `other_invoice_log` ENABLE KEYS */;
DROP TABLE IF EXISTS `other_invoice_payment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `other_invoice_payment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_no` varchar(50) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `pay_jpy_amount` decimal(15,2) DEFAULT NULL,
  `pay_rate` decimal(10,4) DEFAULT NULL,
  `pay_lkr_amount` decimal(15,2) NOT NULL,
  `ref` varchar(255) DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_no` (`invoice_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `other_invoice_payment` DISABLE KEYS */;
/*!40000 ALTER TABLE `other_invoice_payment` ENABLE KEYS */;
DROP TABLE IF EXISTS `passenger_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `passenger_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pass_name` varchar(255) DEFAULT NULL,
  `tour_id` int(11) DEFAULT NULL,
  `manual_tour_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `passenger_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `passenger_details` ENABLE KEYS */;
DROP TABLE IF EXISTS `passenger_pricing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `passenger_pricing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `passenger_id` int(11) NOT NULL,
  `passenger_type` enum('Adult','Child','Infant') NOT NULL,
  `sales_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cost_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `category` varchar(100) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `created_by` varchar(50) NOT NULL,
  `updated_by` varchar(50) DEFAULT NULL,
  `is_refunded` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `passenger_id` (`passenger_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `passenger_pricing` DISABLE KEYS */;
/*!40000 ALTER TABLE `passenger_pricing` ENABLE KEYS */;
DROP TABLE IF EXISTS `passengers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `passengers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) DEFAULT NULL,
  `membership` int(11) DEFAULT NULL,
  `tour_name` varchar(150) DEFAULT NULL,
  `first_name` varchar(150) DEFAULT NULL,
  `surname` varchar(150) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `phone` int(11) DEFAULT NULL,
  `passport_no` varchar(30) DEFAULT NULL,
  `passport_issue_date` date DEFAULT NULL,
  `country` varchar(20) DEFAULT NULL,
  `postal_code` varchar(25) DEFAULT NULL,
  `address` varchar(150) DEFAULT NULL,
  `street` varchar(50) DEFAULT NULL,
  `food_instruction` varchar(50) DEFAULT NULL,
  `additional_details` varchar(250) DEFAULT NULL,
  `passport_copy` varchar(150) DEFAULT NULL,
  `emergency_contact` int(11) DEFAULT NULL,
  `emergency_address1` varchar(200) DEFAULT NULL,
  `emergency_address2` varchar(200) DEFAULT NULL,
  `created_by` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL,
  `middle_name` varchar(150) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `tel_no` varchar(12) NOT NULL,
  `registered` int(11) NOT NULL DEFAULT 0,
  `register_id` int(11) NOT NULL DEFAULT 0,
  `is_primary` int(11) NOT NULL DEFAULT 0,
  `passport_expire_date` date NOT NULL DEFAULT '0000-00-00',
  `auto_surname` varchar(500) NOT NULL DEFAULT '',
  `auto_othernames` varchar(500) NOT NULL DEFAULT '',
  `title` varchar(150) DEFAULT NULL,
  `ticket_status` int(11) DEFAULT 1,
  `status` int(11) DEFAULT 0,
  `ticket_no` varchar(255) DEFAULT NULL,
  `ticket_issue_date` datetime DEFAULT NULL,
  `pass_id` int(11) DEFAULT NULL,
  `payment_flag` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `passengers` DISABLE KEYS */;
/*!40000 ALTER TABLE `passengers` ENABLE KEYS */;
DROP TABLE IF EXISTS `pax_infant`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pax_infant` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) DEFAULT NULL,
  `pax_id` int(11) NOT NULL,
  `infant_name` varchar(500) NOT NULL,
  `dob` date DEFAULT NULL,
  `created_by` varchar(200) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `pax_infant` DISABLE KEYS */;
/*!40000 ALTER TABLE `pax_infant` ENABLE KEYS */;
DROP TABLE IF EXISTS `payment_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer` varchar(100) DEFAULT NULL,
  `sup_lcode` varchar(100) DEFAULT NULL,
  `tour_id` varchar(100) DEFAULT NULL,
  `invoice_no` varchar(100) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `datetime_logged` datetime DEFAULT NULL,
  `logged_user` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `dollar_rate` decimal(10,2) DEFAULT NULL,
  `total_usd` decimal(10,2) DEFAULT NULL,
  `room_cost` decimal(10,2) DEFAULT NULL,
  `transport_cost` decimal(10,2) DEFAULT NULL,
  `hotel_amount` decimal(10,2) DEFAULT NULL,
  `sales_amount` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(10,2) DEFAULT NULL,
  `consignee` varchar(255) DEFAULT NULL,
  `activity_des` text DEFAULT NULL,
  `hotel_des` decimal(10,2) DEFAULT NULL,
  `extra_charges` text DEFAULT NULL,
  `invoice_amounts` text DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `payment_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_history` ENABLE KEYS */;
DROP TABLE IF EXISTS `payment_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_request` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tour_id` varchar(50) NOT NULL,
  `hotel_acc` varchar(50) NOT NULL,
  `manual_id` varchar(50) NOT NULL,
  `consignee` varchar(100) NOT NULL,
  `create_user` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `hotel_name` varchar(100) NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `branch_name` varchar(100) NOT NULL,
  `sup_bank_acc` varchar(100) NOT NULL,
  `total_hotel_pay` varchar(120) NOT NULL,
  `total_hotel_advance` varchar(120) NOT NULL,
  `due_amount` varchar(120) NOT NULL,
  `payment_request` tinyint(1) DEFAULT 1,
  `payment_request_user` varchar(50) NOT NULL,
  `payment_request_date` datetime NOT NULL DEFAULT current_timestamp(),
  `requestAmount` float(50,2) DEFAULT NULL,
  `accept_user` varchar(255) DEFAULT NULL,
  `accept_date` datetime DEFAULT NULL,
  `complete_user` varchar(255) DEFAULT NULL,
  `complete_date` datetime DEFAULT NULL,
  `payment_cancel_date` datetime DEFAULT NULL,
  `payment_cancel_user` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `finalized` tinyint(1) DEFAULT 0,
  `finalized_user` varchar(100) DEFAULT NULL,
  `finalized_date` datetime DEFAULT NULL,
  `is_bulk` int(11) DEFAULT 0,
  `voucher_created` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Set to 1 once a voucher row has been created for this payment request',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `payment_request` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_request` ENABLE KEYS */;
DROP TABLE IF EXISTS `pnr_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pnr_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `pnr_text` varchar(2000) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `pnr_content` DISABLE KEYS */;
/*!40000 ALTER TABLE `pnr_content` ENABLE KEYS */;
DROP TABLE IF EXISTS `quotation_changes_audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `quotation_changes_audit_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `tour_id` varchar(50) NOT NULL,
  `tour_manual_id` varchar(100) DEFAULT NULL,
  `consignee_name` varchar(255) DEFAULT NULL,
  `change_type` varchar(50) NOT NULL COMMENT 'hotel_date, activity, sub_location, itinerary, general, room_count, vehicle_rate, passenger, tour_creation',
  `action_type` enum('INSERT','UPDATE','DELETE') NOT NULL,
  `table_affected` varchar(100) NOT NULL,
  `record_id` varchar(50) DEFAULT NULL,
  `quotation_date` date DEFAULT NULL,
  `quotation_row_id` varchar(10) DEFAULT NULL,
  `field_changed` varchar(100) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `full_data_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`full_data_snapshot`)),
  `change_description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `user_id` varchar(50) NOT NULL,
  `user_name` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `idx_tour_id` (`tour_id`),
  KEY `idx_change_type` (`change_type`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `quotation_changes_audit_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `quotation_changes_audit_log` ENABLE KEYS */;
DROP TABLE IF EXISTS `room_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rtype` varchar(255) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `room_type` DISABLE KEYS */;
/*!40000 ALTER TABLE `room_type` ENABLE KEYS */;
DROP TABLE IF EXISTS `service_packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_packages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_option` int(11) NOT NULL,
  `service_package` varchar(150) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `service_packages` DISABLE KEYS */;
/*!40000 ALTER TABLE `service_packages` ENABLE KEYS */;
DROP TABLE IF EXISTS `sms_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `to` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `system` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `response` text DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL,
  `tour_id` int(11) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_token` (`token`(250))
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `sms_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `sms_log` ENABLE KEYS */;
DROP TABLE IF EXISTS `special_payment_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `special_payment_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tour_id` varchar(255) NOT NULL,
  `manual_id` varchar(255) DEFAULT NULL,
  `agent_name` varchar(255) DEFAULT NULL,
  `consignee` varchar(255) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `pax_count` int(11) DEFAULT NULL,
  `requested_by` varchar(255) NOT NULL,
  `requested_date` datetime NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_user` varchar(255) DEFAULT NULL,
  `admin_action_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tour_id` (`tour_id`(250)),
  KEY `idx_status` (`status`),
  KEY `idx_requested_by` (`requested_by`(250))
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `special_payment_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `special_payment_requests` ENABLE KEYS */;
DROP TABLE IF EXISTS `staff_division`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff_division` (
  `id` smallint(6) NOT NULL AUTO_INCREMENT,
  `division` varchar(60) NOT NULL,
  `del` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `staff_division` DISABLE KEYS */;
/*!40000 ALTER TABLE `staff_division` ENABLE KEYS */;
DROP TABLE IF EXISTS `stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock` (
  `Unit_Cost` decimal(19,4) DEFAULT NULL,
  `Dr_Qty` double DEFAULT 0,
  `Cr_Qty` double DEFAULT 0,
  `Ref` int(11) DEFAULT NULL,
  `Status` varchar(20) DEFAULT NULL,
  `S_Price` decimal(19,4) DEFAULT NULL,
  `Stock_Card` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Stores` smallint(6) DEFAULT NULL,
  `Wrt` smallint(6) DEFAULT NULL,
  `SN` varchar(20) DEFAULT NULL,
  `pPrice` decimal(19,4) DEFAULT NULL,
  `unit_vat` double DEFAULT NULL,
  `L_dis` double DEFAULT NULL,
  `job_flag` smallint(6) DEFAULT NULL,
  `Descrip` varchar(300) DEFAULT NULL,
  `line_dis` decimal(19,4) DEFAULT NULL,
  `unit_Price` decimal(19,4) DEFAULT NULL,
  `tym` datetime DEFAULT NULL,
  `hr` int(11) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `gatepass` int(11) DEFAULT NULL,
  `V_Num` varchar(100) DEFAULT NULL,
  `dmg` smallint(6) DEFAULT NULL,
  `brnch` int(11) DEFAULT NULL,
  `one` smallint(6) DEFAULT NULL,
  `foc` double DEFAULT NULL,
  `Act_prze` decimal(19,4) DEFAULT NULL,
  `new_cost` decimal(19,4) DEFAULT NULL,
  `value` decimal(19,4) DEFAULT NULL,
  `Dr_ml` double DEFAULT NULL,
  `Cr_ml` double DEFAULT NULL,
  `ava_qty` double DEFAULT NULL,
  `flg` int(11) DEFAULT NULL,
  `cus_acc` varchar(50) DEFAULT NULL,
  `Nx` double DEFAULT NULL,
  `Vx` double DEFAULT NULL,
  `R_price` decimal(19,4) DEFAULT NULL,
  `W_price` decimal(19,4) DEFAULT NULL,
  `expire_date` datetime DEFAULT NULL,
  `List_price` decimal(19,4) DEFAULT NULL,
  `Dis_price` decimal(19,4) DEFAULT NULL,
  `vat` decimal(19,4) DEFAULT NULL,
  `nbt` decimal(19,4) DEFAULT NULL,
  `txt` varchar(150) DEFAULT NULL,
  `tn` varchar(50) DEFAULT NULL,
  `index_no` varchar(50) DEFAULT NULL,
  `issued_date` datetime DEFAULT NULL,
  `returned_date` datetime DEFAULT NULL,
  `principal_req` int(11) DEFAULT 0,
  `book_type` varchar(50) DEFAULT NULL,
  `price` longtext DEFAULT NULL,
  `req_by` varchar(50) DEFAULT NULL,
  `app_by` varchar(50) DEFAULT NULL,
  `app_date` datetime DEFAULT NULL,
  `B_Code` varchar(20) NOT NULL,
  PRIMARY KEY (`ID`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `stock` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock` ENABLE KEYS */;
DROP TABLE IF EXISTS `stock_book`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_book` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(255) DEFAULT NULL,
  `Code` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `stock_book` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_book` ENABLE KEYS */;
DROP TABLE IF EXISTS `stock_card`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_card` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `M_Stock` int(11) DEFAULT 0,
  `Stock_Book` int(11) DEFAULT 0,
  `Stock_Cat` int(11) DEFAULT 0,
  `Sales_P` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `Date` datetime DEFAULT NULL,
  `wPrice` decimal(19,4) DEFAULT 0.0000,
  `Sales_P_Max` decimal(19,4) DEFAULT 0.0000,
  `wPrice_Max` decimal(19,4) DEFAULT 0.0000,
  `Rack_Location` varchar(20) DEFAULT '0',
  `Stock_Card` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `DQ` int(11) DEFAULT 0,
  `DP` double DEFAULT 0,
  `eql` smallint(6) DEFAULT 0,
  `model` varchar(100) DEFAULT '0',
  `dp2` decimal(19,4) DEFAULT 0.0000,
  `dq2` int(11) DEFAULT 0,
  `dp3` decimal(19,4) DEFAULT 0.0000,
  `dq3` int(11) DEFAULT 0,
  `Part_No` varchar(100) NOT NULL DEFAULT '0',
  `reorder` smallint(6) NOT NULL DEFAULT 0,
  `dupflag` smallint(6) NOT NULL DEFAULT 0,
  `RL` varchar(20) DEFAULT '0',
  `SL` varchar(20) NOT NULL DEFAULT '0',
  `Row` smallint(6) NOT NULL DEFAULT 0,
  `Col` int(11) NOT NULL DEFAULT 0,
  `pp` varchar(100) DEFAULT '0',
  `vat` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `dis` double NOT NULL DEFAULT 0,
  `tp` double NOT NULL DEFAULT 0,
  `oth` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `iorder` int(11) NOT NULL DEFAULT 0,
  `Max_ordr` int(11) NOT NULL DEFAULT 0,
  `itm` varchar(500) NOT NULL DEFAULT '0',
  `web` smallint(6) NOT NULL DEFAULT 0,
  `bst` smallint(6) NOT NULL DEFAULT 0,
  `SN` tinyint(1) NOT NULL DEFAULT 0,
  `SC` tinyint(1) NOT NULL DEFAULT 0,
  `SCT` tinyint(1) NOT NULL DEFAULT 0,
  `Bar_code` varchar(500) NOT NULL DEFAULT '0',
  `rq_flg` smallint(6) NOT NULL DEFAULT 0,
  `flag` smallint(6) NOT NULL DEFAULT 0,
  `units` double NOT NULL DEFAULT 0,
  `A` double NOT NULL DEFAULT 0,
  `B` double NOT NULL DEFAULT 0,
  `C` double NOT NULL DEFAULT 0,
  `D` double NOT NULL DEFAULT 0,
  `E` double NOT NULL DEFAULT 0,
  `brk` double NOT NULL DEFAULT 0,
  `foc` double NOT NULL DEFAULT 0,
  `upsize_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `CI` int(11) DEFAULT 0,
  `sup2` varchar(100) DEFAULT '0',
  `sup3` varchar(100) DEFAULT '0',
  `sup4` varchar(100) DEFAULT '0',
  `sup5` varchar(100) DEFAULT '0',
  `Account` varchar(50) DEFAULT '0',
  `ml` int(11) DEFAULT 0,
  `pack_sale` decimal(19,4) DEFAULT 0.0000,
  `pack_rest` decimal(19,4) DEFAULT 0.0000,
  `menu` smallint(6) DEFAULT 0,
  `c1` smallint(6) DEFAULT 0,
  `c2` smallint(6) DEFAULT 0,
  `c3` smallint(6) DEFAULT 0,
  `c4` smallint(6) DEFAULT 0,
  `c5` smallint(6) DEFAULT 0,
  `c6` smallint(6) DEFAULT 0,
  `c7` smallint(6) DEFAULT 0,
  `c8` smallint(6) DEFAULT 0,
  `disamt` decimal(19,4) DEFAULT 0.0000,
  `disp` decimal(19,4) DEFAULT 0.0000,
  `Descrip` varchar(250) DEFAULT '0',
  `rq_qty` int(11) DEFAULT 0,
  `rq_sls` double DEFAULT 0,
  `man_qty` int(11) DEFAULT 0,
  `Stock_card_old` varchar(50) DEFAULT '0',
  `RP_margin` decimal(19,4) DEFAULT 0.0000,
  `tender_note` varchar(150) DEFAULT '0',
  `keywords` longtext DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `Author` longtext DEFAULT NULL,
  `Place` longtext DEFAULT NULL,
  `Pages` varchar(50) DEFAULT NULL,
  `tel` varchar(50) DEFAULT NULL,
  `serial_no` varchar(50) DEFAULT NULL,
  `Acc_no` varchar(50) DEFAULT NULL,
  `isbn` longtext DEFAULT NULL,
  `remarks` longtext DEFAULT NULL,
  `book_type` varchar(50) DEFAULT NULL,
  `flag1` int(11) DEFAULT NULL,
  `entrance_fee_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `stock_card` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_card` ENABLE KEYS */;
DROP TABLE IF EXISTS `stock_cat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_cat` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(255) DEFAULT NULL,
  `Code` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `stock_cat` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_cat` ENABLE KEYS */;
DROP TABLE IF EXISTS `stock_cost`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_cost` (
  `Stock_Card` varchar(100) NOT NULL,
  `Avg_Cost` decimal(19,4) NOT NULL,
  `Last_Cost` decimal(19,4) DEFAULT NULL,
  `usern` varchar(50) DEFAULT NULL,
  `date` date DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `stock_cost` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_cost` ENABLE KEYS */;
DROP TABLE IF EXISTS `stock_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Date` datetime DEFAULT NULL,
  `Sup_Acc` varchar(26) DEFAULT NULL,
  `Discount` double DEFAULT NULL,
  `Type` varchar(40) DEFAULT NULL,
  `Invoice` int(11) DEFAULT NULL,
  `Login` varchar(40) DEFAULT NULL,
  `Ref` int(11) DEFAULT NULL,
  `P_Order` longtext DEFAULT NULL,
  `Job` varchar(100) DEFAULT NULL,
  `cashier` varchar(100) DEFAULT NULL,
  `Sales_return` int(11) DEFAULT NULL,
  `Gatepass` int(11) DEFAULT NULL,
  `salesmen` varchar(100) DEFAULT NULL,
  `reson` varchar(100) DEFAULT NULL,
  `Stock_date` datetime DEFAULT NULL,
  `Emp` varchar(100) DEFAULT NULL,
  `divition` varchar(100) DEFAULT NULL,
  `V_Number` varchar(100) DEFAULT NULL,
  `Pending_job` varchar(100) DEFAULT NULL,
  `pflag` smallint(6) DEFAULT NULL,
  `emp_number` varchar(50) DEFAULT NULL,
  `emp_name` varchar(500) DEFAULT NULL,
  `loading` varchar(200) DEFAULT NULL,
  `unloading` varchar(200) DEFAULT NULL,
  `csh` decimal(19,4) DEFAULT NULL,
  `crdt` decimal(19,4) DEFAULT NULL,
  `sum_id` int(11) DEFAULT NULL,
  `v_no` varchar(100) DEFAULT NULL,
  `PO_GRN` varchar(100) DEFAULT NULL,
  `usr` varchar(100) DEFAULT NULL,
  `trns` decimal(19,4) DEFAULT NULL,
  `vat` decimal(19,4) DEFAULT NULL,
  `po` int(11) DEFAULT NULL,
  `res_date` datetime DEFAULT NULL,
  `inv_date` datetime DEFAULT NULL,
  `Dis_Amt` decimal(19,4) DEFAULT NULL,
  `L_Amt` decimal(19,4) DEFAULT NULL,
  `Suplier_name` varchar(150) DEFAULT NULL,
  `item_discount` decimal(19,4) DEFAULT NULL,
  `sales_margin` decimal(19,4) DEFAULT NULL,
  `Vat_p` decimal(19,4) DEFAULT NULL,
  `vat_dis` varchar(50) DEFAULT NULL,
  `VAT_tot` decimal(19,4) DEFAULT NULL,
  `NBT_tot` decimal(19,4) DEFAULT NULL,
  `nbt_p` decimal(19,4) DEFAULT NULL,
  `GRN_no` varchar(50) DEFAULT NULL,
  `Account` varchar(50) DEFAULT NULL,
  `div` int(11) DEFAULT NULL,
  `GRN_Type` varchar(50) DEFAULT NULL,
  `GRN_Total` decimal(19,4) DEFAULT NULL,
  `PO_NO` int(11) DEFAULT NULL,
  `Dis_tot` decimal(19,4) DEFAULT NULL,
  `indx_no` varchar(50) DEFAULT NULL,
  `Note` varchar(500) DEFAULT NULL,
  `B_Code` varchar(10) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `stock_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_orders` ENABLE KEYS */;
DROP TABLE IF EXISTS `stock_return`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_return` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `return_ref` varchar(50) NOT NULL COMMENT 'Return reference number (RTN-xxxx)',
  `stock_card` varchar(50) NOT NULL COMMENT 'Item code from stock_card table',
  `return_date` date NOT NULL COMMENT 'Date of return',
  `quantity` decimal(10,2) NOT NULL COMMENT 'Quantity returned',
  `unit_cost` decimal(10,2) DEFAULT 0.00 COMMENT 'Average cost at time of return',
  `total_value` decimal(10,2) DEFAULT 0.00 COMMENT 'Total value (quantity Ã— unit_cost)',
  `supplier_account` varchar(50) DEFAULT NULL COMMENT 'Supplier account code (L_Code)',
  `reason` varchar(100) DEFAULT NULL COMMENT 'Return reason (Damaged, Expired, etc.)',
  `notes` text DEFAULT NULL COMMENT 'Additional notes',
  `stock_ref` int(11) DEFAULT NULL COMMENT 'Reference to stock table record',
  `usage_note_id` int(11) DEFAULT NULL COMMENT 'FK to usage_note.id (for tour-based returns)',
  `usage_note_no` varchar(50) DEFAULT NULL COMMENT 'Usage note number (for tour-based returns)',
  `tour_id` varchar(50) DEFAULT NULL COMMENT 'Tour that is returning items (for tour-based returns)',
  `original_quantity` int(11) DEFAULT NULL COMMENT 'Original quantity issued (for tour-based returns)',
  `created_by` varchar(50) DEFAULT NULL COMMENT 'Username who created the return',
  `created_at` datetime DEFAULT current_timestamp() COMMENT 'Record creation timestamp',
  `status` enum('pending','processed','cancelled') DEFAULT 'processed' COMMENT 'Return status',
  PRIMARY KEY (`id`),
  UNIQUE KEY `return_ref` (`return_ref`),
  KEY `stock_card` (`stock_card`),
  KEY `return_date` (`return_date`),
  KEY `supplier_account` (`supplier_account`),
  KEY `idx_usage_note_id` (`usage_note_id`),
  KEY `idx_usage_note_no` (`usage_note_no`),
  KEY `idx_tour_id` (`tour_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stock return tracking table';
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `stock_return` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_return` ENABLE KEYS */;
DROP TABLE IF EXISTS `supplier`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `supplier` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Sup_Acc_No` varchar(50) DEFAULT NULL,
  `Name` varchar(240) NOT NULL DEFAULT '0',
  `Address` varchar(300) NOT NULL DEFAULT '0',
  `NIC` varchar(30) DEFAULT '0',
  `TP` varchar(50) DEFAULT NULL,
  `Mobile` varchar(50) DEFAULT '0',
  `flag` int(11) DEFAULT 0,
  `cr_lmt` decimal(19,4) DEFAULT 0.0000,
  `cr_dyz` double DEFAULT 0,
  `csh` smallint(6) DEFAULT 0,
  `chq` smallint(6) DEFAULT 0,
  `chq_fvr` longtext DEFAULT NULL,
  `rep_offce` varchar(50) DEFAULT '0',
  `rep_mobile` varchar(50) DEFAULT '0',
  `ac` smallint(6) DEFAULT NULL,
  `sup_cat` int(11) DEFAULT NULL,
  `ref_no` int(11) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `sup_bank_acc` varchar(255) DEFAULT NULL,
  `bank_id` int(11) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `branch_name` varchar(255) DEFAULT NULL,
  `remark` varchar(1000) DEFAULT NULL,
  `create_user` varchar(255) DEFAULT NULL,
  `dlimg` varchar(255) DEFAULT NULL,
  `glimg` varchar(255) DEFAULT NULL,
  `dled` date DEFAULT NULL,
  `gled` date DEFAULT NULL,
  `language` varchar(255) DEFAULT NULL,
  `acc_holder` varchar(255) DEFAULT NULL,
  `jname` varchar(255) DEFAULT NULL,
  `is_login_enabled` int(11) DEFAULT 0,
  `username` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `bulk_payment` int(11) DEFAULT 0,
  `type` varchar(255) DEFAULT NULL,
  `passenger_count` int(11) DEFAULT NULL,
  `km_rate` double DEFAULT NULL,
  `v_num` varchar(255) DEFAULT NULL,
  `bata` decimal(10,2) DEFAULT NULL,
  `action` int(11) DEFAULT 0,
  `vimg` varchar(255) DEFAULT NULL,
  `vlimg` varchar(255) DEFAULT NULL,
  `vehi_license_expire_date` date DEFAULT NULL,
  `viimg` varchar(255) DEFAULT NULL,
  `vehi_insurance_expire_date` date DEFAULT NULL,
  `hotel_code` varchar(20) DEFAULT NULL,
  `is_avalible` int(11) DEFAULT 1,
  PRIMARY KEY (`ID`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `supplier` DISABLE KEYS */;
INSERT INTO `supplier` VALUES (1,'6-9-11-99001','TEST HOTEL (dummy — not a real hotel)','TEST address — not a real hotel','0','','0',0,0.0000,0,0,0,NULL,'0','0',NULL,1,NULL,'saipath29@gmail.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,1);
/*!40000 ALTER TABLE `supplier` ENABLE KEYS */;
DROP TABLE IF EXISTS `supplier_qq`;
/*!50001 DROP VIEW IF EXISTS `supplier_qq`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `supplier_qq` AS SELECT
 1 AS `HName`,
  1 AS `SDr`,
  1 AS `SCr`,
  1 AS `Invoice_Num`,
  1 AS `status`,
  1 AS `Bal`,
  1 AS `L_Code`,
  1 AS `FirstOfDate`,
  1 AS `PName`,
  1 AS `index_no` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `supplier_qq2`;
/*!50001 DROP VIEW IF EXISTS `supplier_qq2`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `supplier_qq2` AS SELECT
 1 AS `HName`,
  1 AS `SDr`,
  1 AS `SCr`,
  1 AS `Invoice_Num`,
  1 AS `status`,
  1 AS `Bal`,
  1 AS `L_Code`,
  1 AS `FirstOfDate`,
  1 AS `PName`,
  1 AS `index_no`,
  1 AS `Balance_30_Days`,
  1 AS `Balance_60_Days`,
  1 AS `Balance_90_Days`,
  1 AS `Balance_Over_90_Days` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `temp_passengers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `temp_passengers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) DEFAULT NULL,
  `membership` int(11) DEFAULT NULL,
  `tour_name` varchar(150) DEFAULT NULL,
  `first_name` varchar(150) DEFAULT NULL,
  `surname` varchar(150) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `birthday` date NOT NULL DEFAULT '1900-01-01',
  `phone` int(11) DEFAULT NULL,
  `passport_no` varchar(30) DEFAULT NULL,
  `passport_issue_date` date DEFAULT '1900-01-01',
  `country` varchar(20) DEFAULT NULL,
  `postal_code` varchar(25) DEFAULT NULL,
  `address` varchar(150) DEFAULT NULL,
  `street` varchar(50) DEFAULT NULL,
  `food_instruction` varchar(50) DEFAULT NULL,
  `additional_details` varchar(250) DEFAULT NULL,
  `passport_copy` varchar(150) DEFAULT NULL,
  `emergency_contact` int(11) DEFAULT NULL,
  `emergency_address1` varchar(200) DEFAULT NULL,
  `emergency_address2` varchar(200) DEFAULT NULL,
  `created_by` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL,
  `middle_name` varchar(150) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `tel_no` varchar(15) DEFAULT NULL,
  `passport_expire_date` date NOT NULL,
  `auto_surname` varchar(500) NOT NULL DEFAULT '',
  `auto_othernames` varchar(500) NOT NULL DEFAULT '',
  `email2` varchar(150) DEFAULT NULL,
  `email3` varchar(150) DEFAULT NULL,
  `tel_no2` varchar(20) DEFAULT NULL,
  `tel_no3` varchar(20) DEFAULT NULL,
  `register_id` int(11) NOT NULL,
  `is_agent` int(11) DEFAULT NULL,
  `fax` varchar(255) DEFAULT NULL,
  `mil_no` varchar(255) DEFAULT NULL,
  `seat` varchar(255) DEFAULT NULL,
  `Sup_Acc_No` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `temp_passengers` DISABLE KEYS */;
/*!40000 ALTER TABLE `temp_passengers` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_activity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_activity` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entity` varchar(30) NOT NULL,
  `entity_id` int(10) unsigned NOT NULL,
  `intake_id` int(10) unsigned DEFAULT NULL,
  `tour_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(40) NOT NULL,
  `summary` varchar(500) DEFAULT NULL,
  `changes_json` mediumtext DEFAULT NULL,
  `by_user_id` int(10) unsigned DEFAULT NULL,
  `by_user` varchar(100) DEFAULT NULL,
  `by_name` varchar(190) DEFAULT NULL,
  `at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_act_intake` (`intake_id`,`id`),
  KEY `ix_tf_act_tour` (`tour_id`,`id`),
  KEY `ix_tf_act_entity` (`entity`,`entity_id`,`id`),
  KEY `ix_tf_act_user` (`by_user_id`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_activity` DISABLE KEYS */;
INSERT INTO `tf_activity` VALUES (1,'inquiry',1,1,NULL,'created','Inquiry started by hand; handling agent TEST Mail Administrator',NULL,NULL,'mailtestadmin','mailtestadmin','2026-10-08 13:49:16');
INSERT INTO `tf_activity` VALUES (2,'mail_setting',0,NULL,NULL,'mailbox','The bookings mailbox is htl.res@avenirholidays.com (imap: IMAP imap.lolipop.jp:993, SMTP smtp.lolipop.jp:465, login htl.res@avenirholidays.com, not_connected).',NULL,NULL,'mailtestadmin','TEST Mail Administrator','2026-10-08 13:49:16');
/*!40000 ALTER TABLE `tf_activity` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_crew_bata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_crew_bata` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `person_id` int(10) unsigned NOT NULL,
  `bata` decimal(10,2) DEFAULT NULL,
  `overnight` decimal(10,2) DEFAULT NULL,
  `currency` char(3) NOT NULL DEFAULT 'LKR',
  `effective_from` date NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `set_at` datetime NOT NULL,
  `set_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_cb_person` (`person_id`,`effective_from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_crew_bata` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_crew_bata` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_crew_person`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_crew_person` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(190) NOT NULL,
  `nickname` varchar(120) DEFAULT NULL,
  `kind` enum('driver','guide','both') NOT NULL DEFAULT 'driver',
  `category` varchar(10) DEFAULT NULL,
  `category_confirmed` tinyint(1) NOT NULL DEFAULT 1,
  `role_codes` varchar(190) DEFAULT NULL,
  `languages` varchar(190) DEFAULT NULL,
  `japanese_note` varchar(400) DEFAULT NULL,
  `phone` varchar(60) DEFAULT NULL,
  `mobile` varchar(60) DEFAULT NULL,
  `whatsapp` varchar(60) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `home_address` varchar(400) DEFAULT NULL,
  `garage_address` varchar(400) DEFAULT NULL,
  `garage_same` tinyint(1) NOT NULL DEFAULT 0,
  `photo_file` varchar(255) DEFAULT NULL,
  `nic` varchar(40) DEFAULT NULL,
  `id_type` enum('nic','passport') NOT NULL DEFAULT 'nic',
  `licence_no` varchar(60) DEFAULT NULL,
  `licence_expiry` date DEFAULT NULL,
  `licence_file` varchar(255) DEFAULT NULL,
  `guide_licence_no` varchar(60) DEFAULT NULL,
  `guide_licence_expiry` date DEFAULT NULL,
  `guide_licence_file` varchar(255) DEFAULT NULL,
  `supplier_acc` varchar(50) DEFAULT NULL,
  `bata` decimal(10,2) DEFAULT NULL,
  `bata_currency` char(3) DEFAULT NULL,
  `overnight_bata` decimal(10,2) DEFAULT NULL,
  `bank_name` varchar(120) DEFAULT NULL,
  `bank_branch` varchar(120) DEFAULT NULL,
  `bank_account_no` varchar(60) DEFAULT NULL,
  `bank_account_name` varchar(190) DEFAULT NULL,
  `bank_verified` tinyint(1) NOT NULL DEFAULT 0,
  `bank_verified_at` date DEFAULT NULL,
  `bank_verified_by` varchar(100) DEFAULT NULL,
  `payment_remarks` varchar(400) DEFAULT NULL,
  `migrated_from` varchar(50) DEFAULT NULL,
  `migrated_at` datetime DEFAULT NULL,
  `vehicle_reg` varchar(40) DEFAULT NULL,
  `note` varchar(500) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `archived_at` datetime DEFAULT NULL,
  `archived_by` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_cp_name` (`name`),
  KEY `ix_tf_cp_kind` (`kind`,`status`,`archived_at`),
  KEY `ix_tf_cp_acc` (`supplier_acc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_crew_person` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_crew_person` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_crew_rate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_crew_rate` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `person_id` int(10) unsigned NOT NULL,
  `rate_km` decimal(10,2) NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'LKR',
  `effective_from` date NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `set_at` datetime NOT NULL,
  `set_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_cr_person` (`person_id`,`effective_from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_crew_rate` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_crew_rate` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_crew_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_crew_role` (
  `code` varchar(20) NOT NULL,
  `name` varchar(80) NOT NULL,
  `kind` enum('driver','guide') NOT NULL,
  `category` varchar(60) DEFAULT NULL,
  `language` varchar(40) DEFAULT NULL,
  `bata` decimal(10,2) DEFAULT NULL,
  `currency` char(3) NOT NULL DEFAULT 'USD',
  `note` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_crew_role` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_crew_role` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_crew_standard`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_crew_standard` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `item` varchar(20) NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `currency` char(3) NOT NULL DEFAULT 'LKR',
  `effective_from` date NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `set_at` datetime NOT NULL,
  `set_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_tf_cs_item_from` (`item`,`effective_from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_crew_standard` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_crew_standard` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_crew_vehicle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_crew_vehicle` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `person_id` int(10) unsigned NOT NULL,
  `reg_no` varchar(40) DEFAULT NULL,
  `make` varchar(80) DEFAULT NULL,
  `model` varchar(80) DEFAULT NULL,
  `year` smallint(5) unsigned DEFAULT NULL,
  `vehicle_type` enum('car','van','minibus','bus') DEFAULT NULL,
  `roof_type` enum('high','flat') DEFAULT NULL,
  `rate_class` varchar(20) DEFAULT NULL,
  `ownership` enum('own','rented','other_supplier') DEFAULT NULL,
  `seats` smallint(5) unsigned DEFAULT NULL,
  `max_pax_luggage` smallint(5) unsigned DEFAULT NULL,
  `insurance_expiry` date DEFAULT NULL,
  `insurance_file` varchar(255) DEFAULT NULL,
  `licence_expiry` date DEFAULT NULL,
  `licence_file` varchar(255) DEFAULT NULL,
  `note` varchar(400) DEFAULT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_cv_person` (`person_id`,`is_current`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_crew_vehicle` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_crew_vehicle` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_crew_vehicle_photo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_crew_vehicle_photo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vehicle_id` int(10) unsigned NOT NULL,
  `file` varchar(255) NOT NULL,
  `view` enum('exterior','interior','other') NOT NULL DEFAULT 'exterior',
  `caption` varchar(120) DEFAULT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `added_at` datetime NOT NULL,
  `added_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_cvp_vehicle` (`vehicle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_crew_vehicle_photo` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_crew_vehicle_photo` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_crew_voucher`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_crew_voucher` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tour_id` int(10) unsigned NOT NULL,
  `job_id` int(10) unsigned NOT NULL,
  `crew_id` int(10) unsigned NOT NULL,
  `voucher_no` varchar(30) NOT NULL,
  `kind` enum('driver','guide') NOT NULL,
  `role_code` varchar(20) NOT NULL,
  `role_name` varchar(80) NOT NULL,
  `supplier_acc` varchar(50) DEFAULT NULL,
  `person_name` varchar(190) NOT NULL,
  `date_from` date NOT NULL,
  `date_to` date NOT NULL,
  `days` smallint(5) unsigned NOT NULL,
  `rate` decimal(10,2) NOT NULL,
  `rate_source` enum('person','master') NOT NULL DEFAULT 'master',
  `currency` char(3) NOT NULL DEFAULT 'LKR',
  `amount` decimal(12,2) NOT NULL,
  `vehicle_reg` varchar(60) DEFAULT NULL,
  `state` enum('issued','cancelled') NOT NULL DEFAULT 'issued',
  `note` varchar(400) DEFAULT NULL,
  `issued_at` datetime NOT NULL,
  `issued_by` varchar(100) DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancelled_by` varchar(100) DEFAULT NULL,
  `cancel_note` varchar(400) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tf_cvoucher_no` (`voucher_no`),
  KEY `ix_tf_cvoucher_job` (`job_id`),
  KEY `ix_tf_cvoucher_tour` (`tour_id`),
  KEY `ix_tf_cvoucher_crew` (`crew_id`),
  CONSTRAINT `fk_tf_cvoucher_job` FOREIGN KEY (`job_id`) REFERENCES `tf_transport_job` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_crew_voucher` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_crew_voucher` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_doc_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_doc_type` (
  `code` varchar(30) NOT NULL,
  `name` varchar(80) NOT NULL,
  `phase` enum('before','after') NOT NULL DEFAULT 'before',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `updated_by` varchar(100) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_doc_type` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_doc_type` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_driver_adjustment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_driver_adjustment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` int(10) unsigned NOT NULL,
  `label` varchar(120) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `added_at` datetime NOT NULL,
  `added_by` varchar(100) DEFAULT NULL,
  `removed_at` datetime DEFAULT NULL,
  `removed_by` varchar(100) DEFAULT NULL,
  `remove_note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_dadj_job` (`job_id`),
  CONSTRAINT `fk_tf_dadj_job` FOREIGN KEY (`job_id`) REFERENCES `tf_transport_job` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_driver_adjustment` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_driver_adjustment` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_driver_advance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_driver_advance` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tour_id` int(10) unsigned NOT NULL,
  `job_id` int(10) unsigned NOT NULL,
  `crew_id` int(10) unsigned DEFAULT NULL,
  `advance_no` varchar(30) NOT NULL,
  `person_name` varchar(190) DEFAULT NULL,
  `supplier_acc` varchar(50) DEFAULT NULL,
  `currency` char(3) NOT NULL DEFAULT 'LKR',
  `base_hire` decimal(12,2) DEFAULT NULL,
  `base_bata` decimal(12,2) DEFAULT NULL,
  `base_total` decimal(12,2) NOT NULL,
  `base_note` varchar(255) DEFAULT NULL,
  `pct` decimal(6,2) DEFAULT NULL,
  `amount_calc` decimal(12,2) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `adjust_note` varchar(255) DEFAULT NULL,
  `adjusted_by` varchar(100) DEFAULT NULL,
  `adjusted_at` datetime DEFAULT NULL,
  `status` enum('requested','approved','paid','cancelled') NOT NULL DEFAULT 'requested',
  `requested_at` datetime NOT NULL,
  `requested_by` varchar(100) DEFAULT NULL,
  `request_note` varchar(255) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approved_by` varchar(100) DEFAULT NULL,
  `approve_note` varchar(255) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `paid_by` varchar(100) DEFAULT NULL,
  `paid_on` date DEFAULT NULL,
  `method` varchar(30) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancelled_by` varchar(100) DEFAULT NULL,
  `cancel_note` varchar(255) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tf_dadv_no` (`advance_no`),
  KEY `ix_tf_dadv_job` (`job_id`),
  KEY `ix_tf_dadv_tour` (`tour_id`),
  CONSTRAINT `fk_tf_dadv_job` FOREIGN KEY (`job_id`) REFERENCES `tf_transport_job` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_driver_advance` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_driver_advance` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_driver_settlement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_driver_settlement` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tour_id` int(10) unsigned NOT NULL,
  `job_id` int(10) unsigned NOT NULL,
  `settlement_no` varchar(30) NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'LKR',
  `hire` decimal(12,2) DEFAULT NULL,
  `hire_basis` varchar(20) DEFAULT NULL,
  `hire_km` decimal(10,1) DEFAULT NULL,
  `bata` decimal(12,2) DEFAULT NULL,
  `payable` decimal(12,2) NOT NULL,
  `advances` decimal(12,2) NOT NULL DEFAULT 0.00,
  `adjustments` decimal(12,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(12,2) NOT NULL,
  `detail_json` mediumtext DEFAULT NULL,
  `note` varchar(400) DEFAULT NULL,
  `settled_at` datetime NOT NULL,
  `settled_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tf_dset_no` (`settlement_no`),
  UNIQUE KEY `uq_tf_dset_job` (`job_id`),
  CONSTRAINT `fk_tf_dset_job` FOREIGN KEY (`job_id`) REFERENCES `tf_transport_job` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_driver_settlement` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_driver_settlement` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_edit_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_edit_request` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `intake_id` int(10) unsigned NOT NULL,
  `seq` smallint(5) unsigned NOT NULL,
  `status` enum('open','waiting','applied','cancelled') NOT NULL DEFAULT 'open',
  `pasted_text` text NOT NULL,
  `change_to` text NOT NULL,
  `reason` varchar(1000) DEFAULT NULL,
  `was_locked` tinyint(1) NOT NULL DEFAULT 0,
  `tour_id` int(10) unsigned DEFAULT NULL,
  `requested_by` varchar(100) DEFAULT NULL,
  `requested_by_id` int(10) unsigned DEFAULT NULL,
  `requested_at` datetime NOT NULL,
  `answers_json` mediumtext DEFAULT NULL,
  `proposal_json` mediumtext DEFAULT NULL,
  `fingerprint` char(40) DEFAULT NULL,
  `checked_by` varchar(100) DEFAULT NULL,
  `checked_at` datetime DEFAULT NULL,
  `result_json` mediumtext DEFAULT NULL,
  `confirmed_by` varchar(100) DEFAULT NULL,
  `confirmed_by_id` int(10) unsigned DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `quote_mark` int(10) unsigned DEFAULT NULL,
  `cancelled_by` varchar(100) DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancel_note` varchar(255) DEFAULT NULL,
  `activity_id` int(10) unsigned DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_tf_er_seq` (`intake_id`,`seq`),
  KEY `ix_tf_er_status` (`status`,`intake_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_edit_request` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_edit_request` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_email_template`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_email_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `category` varchar(40) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `archived_at` datetime DEFAULT NULL,
  `archived_by` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_et_cat` (`category`,`archived_at`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_email_template` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_email_template` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_excursion_rate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_excursion_rate` (
  `code` varchar(30) NOT NULL,
  `name` varchar(160) NOT NULL,
  `location` varchar(120) DEFAULT NULL,
  `unit` enum('per_person','per_group','per_vehicle') NOT NULL DEFAULT 'per_person',
  `rate` decimal(12,2) NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'USD',
  `includes_transport` tinyint(1) NOT NULL DEFAULT 0,
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `updated_by` varchar(100) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_excursion_rate` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_excursion_rate` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_fee_meta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_fee_meta` (
  `fee_id` int(11) NOT NULL,
  `destination` varchar(120) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`fee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_fee_meta` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_fee_meta` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_free_visit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_free_visit` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `destination` varchar(120) NOT NULL,
  `name` varchar(190) NOT NULL,
  `kind` enum('sightseeing','stop','walk','photo','other') NOT NULL DEFAULT 'sightseeing',
  `description` varchar(1000) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `source` enum('manual','backfill') NOT NULL DEFAULT 'manual',
  `origin` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_free_visit_dest` (`destination`,`active`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_free_visit` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_free_visit` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_google_distance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_google_distance` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `from_key` varchar(190) NOT NULL,
  `to_key` varchar(190) NOT NULL,
  `from_text` varchar(190) NOT NULL,
  `to_text` varchar(190) NOT NULL,
  `km` decimal(10,2) DEFAULT NULL,
  `minutes` int(10) unsigned DEFAULT NULL,
  `status` enum('ok','not_found') NOT NULL DEFAULT 'ok',
  `from_addr` varchar(255) DEFAULT NULL,
  `to_addr` varchar(255) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `fetched_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_tf_gdist_pair` (`from_key`,`to_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_google_distance` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_google_distance` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_hotel_job`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_hotel_job` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tour_id` int(10) unsigned NOT NULL,
  `seq` smallint(5) unsigned NOT NULL DEFAULT 0,
  `day_from` smallint(5) unsigned DEFAULT NULL,
  `day_to` smallint(5) unsigned DEFAULT NULL,
  `hotel_acc` varchar(50) DEFAULT NULL,
  `hotel_id` int(10) unsigned DEFAULT NULL,
  `hotel_name` varchar(190) DEFAULT NULL,
  `area` varchar(160) DEFAULT NULL,
  `check_in` date DEFAULT NULL,
  `check_out` date DEFAULT NULL,
  `nights` smallint(5) unsigned NOT NULL DEFAULT 1,
  `rooms` smallint(5) unsigned DEFAULT NULL,
  `rooms_note` varchar(255) DEFAULT NULL,
  `room_type_text` varchar(120) DEFAULT NULL,
  `meal_plan` varchar(4) DEFAULT NULL,
  `pax` smallint(5) unsigned DEFAULT NULL,
  `special_note` varchar(1000) DEFAULT NULL,
  `crew_rooms` varchar(255) DEFAULT NULL,
  `needs_hotel` tinyint(1) NOT NULL DEFAULT 0,
  `needs_supplier` tinyint(1) NOT NULL DEFAULT 0,
  `needs_meal` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('to_book','requested','confirmed','voucher_issued','problem','cancelled') NOT NULL DEFAULT 'to_book',
  `requested_at` datetime DEFAULT NULL,
  `requested_by` varchar(100) DEFAULT NULL,
  `request_note` varchar(400) DEFAULT NULL,
  `confirmation_ref` varchar(100) DEFAULT NULL,
  `confirmed_with` varchar(160) DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `confirmed_by` varchar(100) DEFAULT NULL,
  `voucher_no` varchar(40) DEFAULT NULL,
  `voucher_at` datetime DEFAULT NULL,
  `voucher_by` varchar(100) DEFAULT NULL,
  `problem_code` varchar(30) DEFAULT NULL,
  `problem_note` varchar(400) DEFAULT NULL,
  `problem_at` datetime DEFAULT NULL,
  `problem_by` varchar(100) DEFAULT NULL,
  `amended` tinyint(1) NOT NULL DEFAULT 0,
  `note` varchar(500) DEFAULT NULL,
  `rcpt_to` varchar(190) DEFAULT NULL,
  `rcpt_cc` varchar(1000) DEFAULT NULL,
  `rcpt_cc_mode` enum('add','replace') NOT NULL DEFAULT 'add',
  `acceptance` enum('pending','confirmed','declined') NOT NULL DEFAULT 'pending',
  `acceptance_at` datetime DEFAULT NULL,
  `acceptance_by` varchar(100) DEFAULT NULL,
  `acceptance_note` varchar(400) DEFAULT NULL,
  `confirmed_rooming` text DEFAULT NULL,
  `request_ref` varchar(90) DEFAULT NULL,
  `hotel_room_status` varchar(60) DEFAULT NULL,
  `hotel_payment_deadline` date DEFAULT NULL,
  `hotel_cancel_deadline` date DEFAULT NULL,
  `hotel_reply_note` varchar(1000) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `archived_at` datetime DEFAULT NULL,
  `archived_by` varchar(100) DEFAULT NULL,
  `archive_note` varchar(400) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_hjob_tour` (`tour_id`,`seq`),
  KEY `ix_tf_hjob_status` (`status`,`check_in`),
  CONSTRAINT `fk_tf_hjob_tour` FOREIGN KEY (`tour_id`) REFERENCES `tf_tour` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_hotel_job` DISABLE KEYS */;
INSERT INTO `tf_hotel_job` VALUES (1,1,0,1,2,'6-9-11-99001',1,'TEST HOTEL (dummy — not a real hotel)',NULL,'2026-12-10','2026-12-12',2,2,NULL,NULL,'BB',4,'TEST ONLY — this is not a real booking. Please do not reserve anything.',NULL,0,0,0,'to_book',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'add','pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-08 13:49:16','mailtestadmin',NULL,NULL,NULL);
/*!40000 ALTER TABLE `tf_hotel_job` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_hotel_message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_hotel_message` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` int(10) unsigned NOT NULL,
  `tour_id` int(10) unsigned NOT NULL,
  `kind` enum('request','voucher','reply','rooming') NOT NULL DEFAULT 'request',
  `channel` enum('email','other') NOT NULL DEFAULT 'email',
  `to_addr` varchar(400) DEFAULT NULL,
  `cc_addr` varchar(400) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `body` mediumtext DEFAULT NULL,
  `state` enum('sent','failed','recorded') NOT NULL DEFAULT 'sent',
  `error` varchar(400) DEFAULT NULL,
  `by` varchar(100) DEFAULT NULL,
  `by_name` varchar(190) DEFAULT NULL,
  `at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_hmsg_job` (`job_id`,`id`),
  KEY `ix_tf_hmsg_tour` (`tour_id`,`id`),
  CONSTRAINT `fk_tf_hmsg_job` FOREIGN KEY (`job_id`) REFERENCES `tf_hotel_job` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_hotel_message` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_hotel_message` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_hotel_room`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_hotel_room` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` int(10) unsigned NOT NULL,
  `tour_id` int(10) unsigned NOT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `label` varchar(40) NOT NULL,
  `room_type` varchar(60) DEFAULT NULL,
  `guests` varchar(1000) NOT NULL,
  `adults` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `children` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `note` varchar(255) DEFAULT NULL,
  `updated_at` datetime NOT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_hroom_job` (`job_id`,`sort_order`),
  KEY `ix_tf_hroom_tour` (`tour_id`),
  CONSTRAINT `fk_tf_hroom_job` FOREIGN KEY (`job_id`) REFERENCES `tf_hotel_job` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_hotel_room` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_hotel_room` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_hotel_send`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_hotel_send` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` int(10) unsigned NOT NULL,
  `tour_id` int(10) unsigned NOT NULL,
  `kind` enum('request','rooming','voucher') NOT NULL DEFAULT 'request',
  `trigger_by` varchar(20) NOT NULL,
  `fingerprint` char(40) NOT NULL,
  `rooming_sig` char(40) DEFAULT NULL,
  `request_ref` varchar(90) DEFAULT NULL,
  `rfc_message_id` varchar(255) DEFAULT NULL,
  `from_addr` varchar(190) DEFAULT NULL,
  `via` enum('staff','company') DEFAULT NULL,
  `to_addr` varchar(190) DEFAULT NULL,
  `cc_addr` varchar(1000) DEFAULT NULL,
  `additional_addr` varchar(1000) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `body` mediumtext DEFAULT NULL,
  `attachments` text DEFAULT NULL,
  `status` enum('sending','sent','failed','blocked','bounced') NOT NULL DEFAULT 'sending',
  `attempts` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `retries` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `last_error` varchar(400) DEFAULT NULL,
  `attempt_log` text DEFAULT NULL,
  `blocked_reason` varchar(255) DEFAULT NULL,
  `duplicate_of` int(10) unsigned DEFAULT NULL,
  `payment_status` varchar(20) DEFAULT NULL,
  `message_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_by_name` varchar(190) DEFAULT NULL,
  `last_attempt_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_hsend_job` (`job_id`,`kind`,`id`),
  KEY `ix_tf_hsend_tour` (`tour_id`),
  KEY `ix_tf_hsend_fp` (`job_id`,`kind`,`fingerprint`),
  CONSTRAINT `fk_tf_hsend_job` FOREIGN KEY (`job_id`) REFERENCES `tf_hotel_job` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_hotel_send` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_hotel_send` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_intake`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_intake` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `inquiry_no` varchar(20) NOT NULL,
  `status` enum('new','working','quoted','confirmed','declined','cancelled','converted') NOT NULL DEFAULT 'new',
  `handler_id` int(10) unsigned DEFAULT NULL,
  `handler_name` varchar(190) DEFAULT NULL,
  `handler_set_by` varchar(100) DEFAULT NULL,
  `handler_set_at` datetime DEFAULT NULL,
  `agent_acc` varchar(50) DEFAULT NULL,
  `agent_name` varchar(190) DEFAULT NULL,
  `agent_email` varchar(190) DEFAULT NULL,
  `country_iso` char(2) DEFAULT NULL,
  `market_code` varchar(10) DEFAULT NULL,
  `tour_name` varchar(190) DEFAULT NULL,
  `arrival_date` date DEFAULT NULL,
  `departure_date` date DEFAULT NULL,
  `nights` smallint(5) unsigned DEFAULT NULL,
  `pax_total` smallint(5) unsigned DEFAULT NULL,
  `pax_adults` smallint(5) unsigned DEFAULT NULL,
  `pax_children` smallint(5) unsigned DEFAULT NULL,
  `rooms_note` varchar(255) DEFAULT NULL,
  `rooms_sgl` smallint(5) unsigned NOT NULL DEFAULT 0,
  `rooms_dbl` smallint(5) unsigned NOT NULL DEFAULT 0,
  `rooms_twn` smallint(5) unsigned NOT NULL DEFAULT 0,
  `rooms_tpl` smallint(5) unsigned NOT NULL DEFAULT 0,
  `hotel_class` varchar(60) DEFAULT NULL,
  `arrival_flight` varchar(160) DEFAULT NULL,
  `departure_flight` varchar(160) DEFAULT NULL,
  `departure_text` varchar(2000) DEFAULT NULL,
  `departure_narrative` text DEFAULT NULL,
  `departure_note` varchar(500) DEFAULT NULL,
  `email_arrival_date` date DEFAULT NULL,
  `email_departure_date` date DEFAULT NULL,
  `dates_confirmed_key` varchar(40) DEFAULT NULL,
  `dates_confirmed_by` varchar(100) DEFAULT NULL,
  `dates_confirmed_at` datetime DEFAULT NULL,
  `source_subject` varchar(400) DEFAULT NULL,
  `source_from` varchar(255) DEFAULT NULL,
  `source_received_at` datetime DEFAULT NULL,
  `source_text` mediumtext DEFAULT NULL,
  `source_text_original` mediumtext DEFAULT NULL,
  `source_edited_at` datetime DEFAULT NULL,
  `source_edited_by` varchar(100) DEFAULT NULL,
  `extract_json` mediumtext DEFAULT NULL,
  `fields_json` mediumtext DEFAULT NULL,
  `extract_model` varchar(60) DEFAULT NULL,
  `extract_ms` int(10) unsigned DEFAULT NULL,
  `extracted_at` datetime DEFAULT NULL,
  `extract_error` varchar(400) DEFAULT NULL,
  `tour_id` varchar(50) DEFAULT NULL,
  `converted_at` datetime DEFAULT NULL,
  `converted_by` varchar(100) DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `confirmed_by` varchar(100) DEFAULT NULL,
  `follow_up_on` date DEFAULT NULL,
  `follow_up_note` varchar(255) DEFAULT NULL,
  `crew_by` enum('auto','human') NOT NULL DEFAULT 'auto',
  `note` text DEFAULT NULL,
  `markup_pct` decimal(6,2) DEFAULT NULL,
  `markup_by` varchar(100) DEFAULT NULL,
  `markup_at` datetime DEFAULT NULL,
  `markup_note` varchar(500) DEFAULT NULL,
  `meal_skip` varchar(400) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `updated_at` datetime NOT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `archived_at` datetime DEFAULT NULL,
  `archived_by` varchar(100) DEFAULT NULL,
  `archive_note` varchar(400) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tf_intake_no` (`inquiry_no`),
  KEY `ix_tf_intake_status` (`status`,`arrival_date`),
  KEY `ix_tf_intake_agent` (`agent_acc`),
  KEY `ix_tf_intake_tour` (`tour_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_intake` DISABLE KEYS */;
INSERT INTO `tf_intake` VALUES (1,'INQ 1615','confirmed',1,'TEST Mail Administrator','mailtestadmin','2026-10-08 13:49:16',NULL,'TEST AGENT (dummy)',NULL,NULL,NULL,'TEST — e-mail workflow test, not a real booking','2026-12-10','2026-12-12',2,4,4,0,NULL,0,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'{\"agent_name\":{\"value\":\"TEST AGENT (dummy)\",\"state\":\"stated\",\"quote\":null,\"basis\":\"Entered by mailtestadmin\",\"origin\":\"human\"},\"tour_name\":{\"value\":\"TEST — e-mail workflow test, not a real booking\",\"state\":\"stated\",\"quote\":null,\"basis\":\"Entered by mailtestadmin\",\"origin\":\"human\"},\"arrival_date\":{\"value\":\"2026-12-10\",\"state\":\"stated\",\"quote\":null,\"basis\":\"Entered by mailtestadmin\",\"origin\":\"human\"},\"nights\":{\"value\":2,\"state\":\"derived\",\"quote\":null,\"basis\":\"Counted from 10 Dec 2026 to 12 Dec 2026.\",\"origin\":\"derived\",\"claimed\":2,\"last\":2},\"departure_date\":{\"value\":\"2026-12-12\",\"state\":\"stated\",\"quote\":null,\"basis\":\"Entered by mailtestadmin\",\"origin\":\"human\"},\"pax_total\":{\"value\":4,\"state\":\"stated\",\"quote\":null,\"basis\":\"Entered by mailtestadmin\",\"origin\":\"human\"},\"pax_adults\":{\"value\":4,\"state\":\"stated\",\"quote\":null,\"basis\":\"Entered by mailtestadmin\",\"origin\":\"human\"},\"pax_children\":{\"value\":0,\"state\":\"stated\",\"quote\":null,\"basis\":\"Entered by mailtestadmin\",\"origin\":\"human\"}}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-08 13:49:16','mailtestadmin',NULL,NULL,'auto',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-08 13:49:16','mailtestadmin','2026-10-08 13:49:16','mailtestadmin',NULL,NULL,NULL);
/*!40000 ALTER TABLE `tf_intake` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_intake_accom`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_intake_accom` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `intake_id` int(10) unsigned NOT NULL,
  `kind` enum('driver','guide') NOT NULL,
  `day_no` smallint(5) unsigned NOT NULL DEFAULT 0,
  `required` tinyint(1) NOT NULL DEFAULT 0,
  `nights` smallint(5) unsigned DEFAULT NULL,
  `nights_by` enum('counted','override') NOT NULL DEFAULT 'counted',
  `rate` decimal(10,2) DEFAULT NULL,
  `currency` char(3) NOT NULL DEFAULT 'USD',
  `rate_by` enum('hotel_policy','company_rate','manual','none') NOT NULL DEFAULT 'none',
  `amount` decimal(12,2) DEFAULT NULL,
  `complimentary_nights` smallint(5) unsigned NOT NULL DEFAULT 0,
  `unknown_nights` smallint(5) unsigned NOT NULL DEFAULT 0,
  `detail_json` mediumtext DEFAULT NULL,
  `why` varchar(400) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tf_accom` (`intake_id`,`kind`,`day_no`),
  CONSTRAINT `fk_tf_accom_intake` FOREIGN KEY (`intake_id`) REFERENCES `tf_intake` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_intake_accom` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_intake_accom` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_intake_crew`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_intake_crew` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `intake_id` int(10) unsigned NOT NULL,
  `role_code` varchar(20) NOT NULL,
  `role_name` varchar(80) NOT NULL,
  `kind` enum('driver','guide') NOT NULL,
  `language` varchar(40) DEFAULT NULL,
  `days` smallint(5) unsigned DEFAULT NULL,
  `days_by` enum('auto','human') DEFAULT NULL,
  `bata` decimal(10,2) DEFAULT NULL,
  `currency` char(3) NOT NULL DEFAULT 'USD',
  `amount` decimal(12,2) DEFAULT NULL,
  `chosen` enum('auto','manual') NOT NULL DEFAULT 'auto',
  `supplier_acc` varchar(50) DEFAULT NULL,
  `person_name` varchar(190) DEFAULT NULL,
  `person_bata` decimal(10,2) DEFAULT NULL,
  `why` varchar(255) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tf_crew` (`intake_id`,`role_code`),
  CONSTRAINT `fk_tf_crew_intake` FOREIGN KEY (`intake_id`) REFERENCES `tf_intake` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_intake_crew` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_intake_crew` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_intake_day`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_intake_day` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `intake_id` int(10) unsigned NOT NULL,
  `day_no` smallint(5) unsigned NOT NULL,
  `day_date` date DEFAULT NULL,
  `area` varchar(160) DEFAULT NULL,
  `hotel_name_raw` varchar(190) DEFAULT NULL,
  `hotel_id` int(10) unsigned DEFAULT NULL,
  `hotel_acc` varchar(50) DEFAULT NULL,
  `match_note` varchar(190) DEFAULT NULL,
  `meal_plan` varchar(4) DEFAULT NULL,
  `hb_meal` enum('lunch','dinner') DEFAULT NULL,
  `meal_source` varchar(12) DEFAULT NULL,
  `meal_quote` varchar(190) DEFAULT NULL,
  `rooms` smallint(5) unsigned DEFAULT NULL,
  `room_type_id` int(10) unsigned DEFAULT NULL,
  `room_type_name` varchar(120) DEFAULT NULL,
  `room_type_auto` tinyint(1) NOT NULL DEFAULT 0,
  `room_type_text` varchar(120) DEFAULT NULL,
  `nights` smallint(5) unsigned NOT NULL DEFAULT 1,
  `rate_state` enum('rate','no_plan','no_supplier','not_in_panel','nothing_named','not_checked') NOT NULL DEFAULT 'not_checked',
  `rate_note` varchar(255) DEFAULT NULL,
  `rate_plan_id` int(10) unsigned DEFAULT NULL,
  `rate_plan_name` varchar(160) DEFAULT NULL,
  `rate_sgl` decimal(10,2) DEFAULT NULL,
  `rate_dbl` decimal(10,2) DEFAULT NULL,
  `rate_tpl` decimal(10,2) DEFAULT NULL,
  `rate_unit` decimal(10,2) DEFAULT NULL,
  `unit_pax_max` smallint(5) unsigned DEFAULT NULL,
  `rate_currency` char(3) DEFAULT NULL,
  `rate_supp_json` text DEFAULT NULL,
  `room_types_json` text DEFAULT NULL,
  `rate_frozen_at` datetime DEFAULT NULL,
  `rate_key` varchar(120) DEFAULT NULL,
  `rate_drift_json` text DEFAULT NULL,
  `checked_at` datetime DEFAULT NULL,
  `narrative` text DEFAULT NULL,
  `special_note` varchar(500) DEFAULT NULL,
  `client_text` varchar(2000) DEFAULT NULL,
  `origin` enum('ai','human') NOT NULL DEFAULT 'ai',
  `source_quote` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_day_intake` (`intake_id`,`day_no`),
  CONSTRAINT `fk_tf_day_intake` FOREIGN KEY (`intake_id`) REFERENCES `tf_intake` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_intake_day` DISABLE KEYS */;
INSERT INTO `tf_intake_day` VALUES (1,1,1,'2026-12-10',NULL,'TEST HOTEL (dummy — not a real hotel)',1,'6-9-11-99001',NULL,'BB',NULL,NULL,NULL,2,NULL,NULL,0,NULL,1,'nothing_named',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'human',NULL);
INSERT INTO `tf_intake_day` VALUES (2,1,2,'2026-12-11',NULL,'TEST HOTEL (dummy — not a real hotel)',1,'6-9-11-99001',NULL,'BB',NULL,NULL,NULL,2,NULL,NULL,0,NULL,1,'nothing_named',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'human',NULL);
/*!40000 ALTER TABLE `tf_intake_day` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_intake_day_photo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_intake_day_photo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `intake_id` int(10) unsigned NOT NULL,
  `day_no` smallint(6) NOT NULL,
  `photo_id` int(10) unsigned DEFAULT NULL,
  `tag_code` varchar(40) DEFAULT NULL,
  `chosen_by` enum('auto','human') NOT NULL DEFAULT 'auto',
  `chosen_at` datetime DEFAULT NULL,
  `by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tf_dayphoto` (`intake_id`,`day_no`),
  CONSTRAINT `fk_tf_dayphoto_intake` FOREIGN KEY (`intake_id`) REFERENCES `tf_intake` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_intake_day_photo` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_intake_day_photo` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_intake_excursion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_intake_excursion` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `intake_id` int(10) unsigned NOT NULL,
  `day_no` smallint(5) unsigned NOT NULL DEFAULT 0,
  `group_id` int(10) unsigned DEFAULT NULL,
  `code` varchar(30) NOT NULL,
  `name` varchar(160) NOT NULL,
  `unit` enum('per_person','per_group','per_vehicle') NOT NULL DEFAULT 'per_person',
  `qty` decimal(10,2) NOT NULL DEFAULT 1.00,
  `rate` decimal(12,2) NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'USD',
  `amount` decimal(12,2) DEFAULT NULL,
  `includes_transport` tinyint(1) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ix_tf_exc_intake` (`intake_id`,`day_no`,`sort_order`),
  CONSTRAINT `fk_tf_exc_intake` FOREIGN KEY (`intake_id`) REFERENCES `tf_intake` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_intake_excursion` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_intake_excursion` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_intake_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_intake_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `intake_id` int(10) unsigned NOT NULL,
  `code` varchar(10) NOT NULL DEFAULT 'A',
  `name` varchar(80) NOT NULL,
  `pax` smallint(5) unsigned NOT NULL DEFAULT 0,
  `day_from` smallint(5) unsigned DEFAULT NULL,
  `day_to` smallint(5) unsigned DEFAULT NULL,
  `rooms_sgl` smallint(5) unsigned NOT NULL DEFAULT 0,
  `rooms_dbl` smallint(5) unsigned NOT NULL DEFAULT 0,
  `rooms_twn` smallint(5) unsigned NOT NULL DEFAULT 0,
  `rooms_tpl` smallint(5) unsigned NOT NULL DEFAULT 0,
  `note` varchar(255) DEFAULT NULL,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ix_tf_group_intake` (`intake_id`,`sort_order`),
  CONSTRAINT `fk_tf_group_intake` FOREIGN KEY (`intake_id`) REFERENCES `tf_intake` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_intake_group` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_intake_group` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_intake_pax`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_intake_pax` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `intake_id` int(10) unsigned NOT NULL,
  `full_name` varchar(160) NOT NULL,
  `room_type` varchar(20) DEFAULT NULL,
  `note` varchar(160) DEFAULT NULL,
  `origin` enum('ai','human') NOT NULL DEFAULT 'ai',
  `group_id` int(10) unsigned DEFAULT NULL,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ix_tf_pax_intake` (`intake_id`,`sort_order`),
  CONSTRAINT `fk_tf_pax_intake` FOREIGN KEY (`intake_id`) REFERENCES `tf_intake` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_intake_pax` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_intake_pax` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_intake_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_intake_request` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `intake_id` int(10) unsigned NOT NULL,
  `kind` enum('request','question') NOT NULL DEFAULT 'request',
  `title` varchar(255) NOT NULL,
  `quote_text` varchar(1000) DEFAULT NULL,
  `status` enum('open','answered','done','dropped') NOT NULL DEFAULT 'open',
  `answer` varchar(1000) DEFAULT NULL,
  `answered_by` varchar(100) DEFAULT NULL,
  `answered_at` datetime DEFAULT NULL,
  `origin` enum('ai','human') NOT NULL DEFAULT 'ai',
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ix_tf_req_intake` (`intake_id`,`sort_order`),
  CONSTRAINT `fk_tf_req_intake` FOREIGN KEY (`intake_id`) REFERENCES `tf_intake` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_intake_request` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_intake_request` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_intake_sector`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_intake_sector` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `intake_id` int(10) unsigned NOT NULL,
  `day_no` smallint(5) unsigned NOT NULL DEFAULT 0,
  `seq` smallint(5) unsigned NOT NULL DEFAULT 0,
  `vehicle_seq` smallint(6) NOT NULL DEFAULT 0,
  `from_place` varchar(190) DEFAULT NULL,
  `to_place` varchar(190) DEFAULT NULL,
  `km` decimal(10,2) DEFAULT NULL,
  `km_by` enum('history','town_history','city_allowance','manual','google','actual','none','same_place') NOT NULL DEFAULT 'none',
  `extra_km` decimal(10,2) DEFAULT NULL,
  `extra_by` enum('history','town_history','city_allowance','manual','none') NOT NULL DEFAULT 'none',
  `samples` smallint(5) unsigned NOT NULL DEFAULT 0,
  `samples_dropped` smallint(5) unsigned NOT NULL DEFAULT 0,
  `samples_post` smallint(5) unsigned NOT NULL DEFAULT 0,
  `km_low` decimal(10,2) DEFAULT NULL,
  `km_high` decimal(10,2) DEFAULT NULL,
  `google_km` decimal(10,2) DEFAULT NULL,
  `google_by` enum('google','not_found','no_key','error','none') NOT NULL DEFAULT 'none',
  `google_why` varchar(255) DEFAULT NULL,
  `actual_km` decimal(10,2) DEFAULT NULL,
  `actual_by` enum('history','town_history','manual','none') NOT NULL DEFAULT 'none',
  `actual_why` varchar(400) DEFAULT NULL,
  `kind` enum('transfer','local','arrival','departure','positioning','idle','garage') NOT NULL DEFAULT 'transfer',
  `origin` enum('auto','human') NOT NULL DEFAULT 'auto',
  `why` varchar(400) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `waived_by` varchar(190) DEFAULT NULL,
  `removed_at` datetime DEFAULT NULL,
  `removed_by` varchar(100) DEFAULT NULL,
  `plan_key` varchar(400) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_sector_intake` (`intake_id`,`day_no`,`seq`),
  CONSTRAINT `fk_tf_sector_intake` FOREIGN KEY (`intake_id`) REFERENCES `tf_intake` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_intake_sector` DISABLE KEYS */;
INSERT INTO `tf_intake_sector` VALUES (3,1,1,0,0,'Colombo','Katunayake',NULL,'none',NULL,'none',0,0,0,NULL,NULL,NULL,'no_key','No Google Maps key is set — add GOOGLE_MAPS_API_KEY to the project\'s .env file.',NULL,'none','The company has no record of driving Colombo to Katunayake, so no distance is offered. Type one in.','positioning','auto','The company has no record of driving Colombo to Katunayake, so no distance is offered. Type one in.','The vehicle runs out to the airport to collect the party. This is how the company works: 1,194 of 1,729 tours on file start with this leg.',NULL,NULL,NULL,'0:1:colombo→katunayake:positioning');
INSERT INTO `tf_intake_sector` VALUES (4,1,1,1,0,'Katunayake',NULL,NULL,'none',NULL,'none',0,0,0,NULL,NULL,NULL,'none',NULL,NULL,'none',NULL,'arrival','auto','Both ends of the leg have to be named before a distance can be looked up.','No area or hotel on day 1 yet, so where they are driven to is not known.',NULL,NULL,NULL,'0:1:katunayake→:arrival');
/*!40000 ALTER TABLE `tf_intake_sector` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_intake_service`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_intake_service` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `intake_id` int(10) unsigned NOT NULL,
  `day_no` smallint(6) NOT NULL DEFAULT 0,
  `kind` enum('visit','entrance','safari','train','ayurveda','lunch','dinner','guide','transport','other') NOT NULL DEFAULT 'other',
  `title` varchar(255) NOT NULL,
  `source_title` varchar(255) DEFAULT NULL,
  `fee_id` int(11) DEFAULT NULL,
  `fee_code` varchar(100) DEFAULT NULL,
  `place_id` int(10) unsigned DEFAULT NULL,
  `rate_id` int(10) unsigned DEFAULT NULL,
  `free_visit_id` int(10) unsigned DEFAULT NULL,
  `match_state` enum('matched','not_in_list','nothing_named','not_checked','no_charge','not_in_master','rate_missing') NOT NULL DEFAULT 'not_checked',
  `match_note` varchar(255) DEFAULT NULL,
  `suggest_code` varchar(100) DEFAULT NULL,
  `pax` smallint(6) DEFAULT NULL,
  `qty` decimal(10,2) DEFAULT NULL,
  `unit_usd` decimal(10,2) DEFAULT NULL,
  `child_usd` decimal(10,2) DEFAULT NULL,
  `rate_currency` char(3) DEFAULT NULL,
  `rate_adult` decimal(12,2) DEFAULT NULL,
  `rate_child` decimal(12,2) DEFAULT NULL,
  `jeep_rate_id` int(10) unsigned DEFAULT NULL,
  `jeep_usd` decimal(10,2) DEFAULT NULL,
  `meal_id` int(10) unsigned DEFAULT NULL,
  `meal_auto` tinyint(1) NOT NULL DEFAULT 0,
  `qty_adult` smallint(6) DEFAULT NULL,
  `qty_child` smallint(6) DEFAULT NULL,
  `meal_time` varchar(5) DEFAULT NULL,
  `tax_pct` decimal(6,2) DEFAULT NULL,
  `tax_cur` decimal(12,2) DEFAULT NULL,
  `amount_cur` decimal(12,2) DEFAULT NULL,
  `meal_ok_note` varchar(255) DEFAULT NULL,
  `meal_email` varchar(190) DEFAULT NULL,
  `meal_contact` varchar(120) DEFAULT NULL,
  `meal_phone` varchar(60) DEFAULT NULL,
  `amount_usd` decimal(12,2) DEFAULT NULL,
  `usd_rate` decimal(10,2) DEFAULT NULL,
  `rate_date` date DEFAULT NULL,
  `amount_lkr` decimal(12,2) DEFAULT NULL,
  `pay_mode` enum('in_tour','prepaid','direct','own_arrangement','not_arranged','unknown','no_charge') NOT NULL DEFAULT 'unknown',
  `special_note` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `source_quote` varchar(1000) DEFAULT NULL,
  `origin` enum('ai','human') NOT NULL DEFAULT 'ai',
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `checked_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_svc_intake` (`intake_id`,`day_no`,`sort_order`),
  KEY `ix_tf_svc_code` (`fee_code`),
  CONSTRAINT `fk_tf_svc_intake` FOREIGN KEY (`intake_id`) REFERENCES `tf_intake` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_intake_service` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_intake_service` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_intake_vehicle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_intake_vehicle` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `intake_id` int(10) unsigned NOT NULL,
  `group_id` int(10) unsigned DEFAULT NULL,
  `day_from` smallint(5) unsigned DEFAULT NULL,
  `day_to` smallint(5) unsigned DEFAULT NULL,
  `label` varchar(40) DEFAULT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `class_code` varchar(20) DEFAULT NULL,
  `type` varchar(120) NOT NULL,
  `seats` smallint(5) unsigned DEFAULT NULL,
  `qty` smallint(5) unsigned NOT NULL DEFAULT 1,
  `km_rate` decimal(10,2) DEFAULT NULL,
  `rate_id` int(10) unsigned DEFAULT NULL,
  `master_rate` decimal(10,2) DEFAULT NULL,
  `rate_by` enum('master','manual','none') NOT NULL DEFAULT 'none',
  `rate_note` varchar(200) DEFAULT NULL,
  `rate_set_by` varchar(100) DEFAULT NULL,
  `rate_set_at` datetime DEFAULT NULL,
  `bata` decimal(10,2) DEFAULT NULL,
  `currency` char(3) NOT NULL DEFAULT 'LKR',
  `chosen` enum('auto','manual') NOT NULL DEFAULT 'auto',
  `why` varchar(255) DEFAULT NULL,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ix_tf_veh_intake` (`intake_id`,`sort_order`),
  CONSTRAINT `fk_tf_veh_intake` FOREIGN KEY (`intake_id`) REFERENCES `tf_intake` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_intake_vehicle` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_intake_vehicle` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_intake_vehicle_day`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_intake_vehicle_day` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `intake_id` int(10) unsigned NOT NULL,
  `vehicle_seq` smallint(6) NOT NULL DEFAULT 0,
  `day_no` smallint(5) unsigned NOT NULL,
  `retained` tinyint(1) NOT NULL DEFAULT 1,
  `note` varchar(255) DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tf_vday` (`intake_id`,`vehicle_seq`,`day_no`),
  CONSTRAINT `fk_tf_vday_intake` FOREIGN KEY (`intake_id`) REFERENCES `tf_intake` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_intake_vehicle_day` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_intake_vehicle_day` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_mail_account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_mail_account` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role` enum('bookings','rates') NOT NULL,
  `address` varchar(190) NOT NULL,
  `display_name` varchar(190) DEFAULT NULL,
  `provider` enum('none','simulated','graph','gmail','service','imap') NOT NULL DEFAULT 'none',
  `server_json` text DEFAULT NULL,
  `secret_enc` text DEFAULT NULL,
  `secret_set_at` datetime DEFAULT NULL,
  `secret_set_by` varchar(100) DEFAULT NULL,
  `status` enum('not_connected','connected','error') NOT NULL DEFAULT 'not_connected',
  `last_error` varchar(400) DEFAULT NULL,
  `cursor_state` varchar(400) DEFAULT NULL,
  `last_sync_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tf_mail_account_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_mail_account` DISABLE KEYS */;
INSERT INTO `tf_mail_account` VALUES (1,'bookings','htl.res@avenirholidays.com','Avenir Holidays — Hotel Reservations','imap','{\"imap_host\":\"imap.lolipop.jp\",\"imap_port\":993,\"smtp_host\":\"smtp.lolipop.jp\",\"smtp_port\":465,\"username\":\"htl.res@avenirholidays.com\"}',NULL,NULL,NULL,'not_connected',NULL,NULL,NULL,'2026-10-08 13:49:16','mailtestadmin');
/*!40000 ALTER TABLE `tf_mail_account` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_mail_attachment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_mail_attachment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `mail_id` int(10) unsigned NOT NULL,
  `filename` varchar(255) NOT NULL,
  `mime` varchar(120) DEFAULT NULL,
  `size` int(10) unsigned NOT NULL DEFAULT 0,
  `sha256` char(64) NOT NULL,
  `path` varchar(255) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_mail_att_mail` (`mail_id`),
  CONSTRAINT `fk_tf_mail_att_mail` FOREIGN KEY (`mail_id`) REFERENCES `tf_mail_message` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_mail_attachment` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_mail_attachment` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_mail_message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_mail_message` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `account_role` enum('bookings','rates') NOT NULL,
  `direction` enum('in','out') NOT NULL DEFAULT 'in',
  `message_id` varchar(255) NOT NULL,
  `in_reply_to` varchar(255) DEFAULT NULL,
  `refs` text DEFAULT NULL,
  `from_addr` varchar(190) DEFAULT NULL,
  `from_name` varchar(190) DEFAULT NULL,
  `to_addr` text DEFAULT NULL,
  `cc_addr` text DEFAULT NULL,
  `subject` varchar(500) DEFAULT NULL,
  `body_text` mediumtext DEFAULT NULL,
  `body_html` mediumtext DEFAULT NULL,
  `sent_date` datetime DEFAULT NULL,
  `received_at` datetime NOT NULL,
  `kind` enum('reply','bounce','rate','other') NOT NULL DEFAULT 'other',
  `state` enum('new','applied','review','linked','rate_draft','dismissed') NOT NULL DEFAULT 'new',
  `tour_id` int(10) unsigned DEFAULT NULL,
  `job_id` int(10) unsigned DEFAULT NULL,
  `send_id` int(10) unsigned DEFAULT NULL,
  `import_id` int(10) unsigned DEFAULT NULL,
  `match_method` varchar(40) DEFAULT NULL,
  `match_confidence` enum('certain','likely','none') DEFAULT NULL,
  `extracted_json` text DEFAULT NULL,
  `raw_sha256` char(64) DEFAULT NULL,
  `raw_path` varchar(255) DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tf_mail_msg` (`account_role`,`message_id`(190)),
  KEY `ix_tf_mail_job` (`job_id`),
  KEY `ix_tf_mail_tour` (`tour_id`),
  KEY `ix_tf_mail_state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_mail_message` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_mail_message` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_mail_notice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_mail_notice` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `kind` enum('hotel_confirmed','all_hotels_confirmed') NOT NULL,
  `tour_id` int(10) unsigned NOT NULL,
  `job_id` int(10) unsigned DEFAULT NULL,
  `mail_id` int(10) unsigned DEFAULT NULL,
  `how` enum('auto','review') NOT NULL DEFAULT 'auto',
  `title` varchar(255) NOT NULL,
  `detail` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_mail_notice_created` (`created_at`),
  KEY `ix_tf_mail_notice_tour` (`tour_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_mail_notice` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_mail_notice` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_mail_notice_seen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_mail_notice_seen` (
  `username` varchar(100) NOT NULL,
  `last_id` int(10) unsigned NOT NULL DEFAULT 0,
  `seen_at` datetime NOT NULL,
  PRIMARY KEY (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_mail_notice_seen` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_mail_notice_seen` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_mail_review`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_mail_review` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `mail_id` int(10) unsigned NOT NULL,
  `kind` enum('reply','unmatched','bounce','rate') NOT NULL,
  `job_id` int(10) unsigned DEFAULT NULL,
  `reason` varchar(1000) DEFAULT NULL,
  `proposal_json` text DEFAULT NULL,
  `status` enum('open','applied','dismissed') NOT NULL DEFAULT 'open',
  `decided_at` datetime DEFAULT NULL,
  `decided_by` varchar(190) DEFAULT NULL,
  `decision_note` varchar(400) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_mail_review_status` (`status`),
  KEY `fk_tf_mail_review_mail` (`mail_id`),
  CONSTRAINT `fk_tf_mail_review_mail` FOREIGN KEY (`mail_id`) REFERENCES `tf_mail_message` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_mail_review` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_mail_review` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_meal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_meal` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) NOT NULL,
  `meal_type` enum('lunch','dinner') NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `supplier_acc` varchar(50) DEFAULT NULL,
  `restaurant` varchar(190) NOT NULL,
  `location` varchar(120) NOT NULL,
  `address` varchar(300) DEFAULT NULL,
  `contact_name` varchar(120) DEFAULT NULL,
  `contact_phone` varchar(60) DEFAULT NULL,
  `contact_email` varchar(190) DEFAULT NULL,
  `cuisine` varchar(80) DEFAULT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `adult_rate` decimal(12,2) NOT NULL DEFAULT 0.00,
  `child_rate` decimal(12,2) DEFAULT NULL,
  `currency` char(3) NOT NULL DEFAULT 'USD',
  `adult_lkr` decimal(12,2) DEFAULT NULL,
  `child_lkr` decimal(12,2) DEFAULT NULL,
  `tax_pct` decimal(6,2) NOT NULL DEFAULT 0.00,
  `tax_included` tinyint(1) NOT NULL DEFAULT 0,
  `tax_note` varchar(190) DEFAULT NULL,
  `valid_from` date NOT NULL,
  `valid_to` date DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `remarks` varchar(1000) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_tf_meal_code` (`code`),
  KEY `ix_tf_meal_type` (`meal_type`,`active`,`valid_from`),
  KEY `ix_tf_meal_location` (`location`),
  KEY `ix_tf_meal_supplier` (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_meal` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_meal` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_meal_route`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_meal_route` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `meal_id` int(10) unsigned NOT NULL,
  `from_place` varchar(120) NOT NULL,
  `to_place` varchar(120) NOT NULL,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ix_tf_meal_route_meal` (`meal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_meal_route` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_meal_route` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_meal_voucher`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_meal_voucher` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tour_id` int(10) unsigned NOT NULL,
  `job_id` int(10) unsigned NOT NULL,
  `service_id` int(10) unsigned DEFAULT NULL,
  `meal_id` int(10) unsigned NOT NULL,
  `voucher_no` varchar(30) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `supplier_acc` varchar(50) DEFAULT NULL,
  `restaurant` varchar(190) NOT NULL,
  `location` varchar(120) DEFAULT NULL,
  `meal_type` enum('lunch','dinner') NOT NULL,
  `on_date` date DEFAULT NULL,
  `meal_time` varchar(5) DEFAULT NULL,
  `adults` smallint(6) DEFAULT NULL,
  `children` smallint(6) DEFAULT NULL,
  `adult_lkr` decimal(12,2) NOT NULL,
  `child_lkr` decimal(12,2) DEFAULT NULL,
  `tax_pct` decimal(6,2) DEFAULT NULL,
  `tax_included` tinyint(1) NOT NULL DEFAULT 0,
  `tax_lkr` decimal(14,2) DEFAULT NULL,
  `amount_lkr` decimal(14,2) NOT NULL,
  `special_note` varchar(500) DEFAULT NULL,
  `to_email` varchar(190) DEFAULT NULL,
  `contact` varchar(190) DEFAULT NULL,
  `mail_status` enum('not_sent','sent','failed','other') NOT NULL DEFAULT 'not_sent',
  `mail_error` varchar(500) DEFAULT NULL,
  `mail_cc` varchar(500) DEFAULT NULL,
  `mail_subject` varchar(255) DEFAULT NULL,
  `mail_body` mediumtext DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `sent_by` varchar(100) DEFAULT NULL,
  `state` enum('issued','cancelled') NOT NULL DEFAULT 'issued',
  `note` varchar(400) DEFAULT NULL,
  `issued_at` datetime NOT NULL,
  `issued_by` varchar(100) DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancelled_by` varchar(100) DEFAULT NULL,
  `cancel_note` varchar(400) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tf_mvoucher_no` (`voucher_no`),
  KEY `ix_tf_mvoucher_job` (`job_id`),
  KEY `ix_tf_mvoucher_tour` (`tour_id`),
  KEY `ix_tf_mvoucher_service` (`service_id`),
  KEY `ix_tf_mvoucher_meal` (`meal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_meal_voucher` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_meal_voucher` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_meta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_meta` (
  `k` varchar(40) NOT NULL,
  `v` text DEFAULT NULL,
  PRIMARY KEY (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_meta` DISABLE KEYS */;
INSERT INTO `tf_meta` VALUES ('acceptance_backfilled','2026-10-06 19:20:03');
INSERT INTO `tf_meta` VALUES ('company_address','No. 126/1, Park Road, Colombo 05, Sri Lanka');
INSERT INTO `tf_meta` VALUES ('company_email','traveljp@avenirholidays.com, land@avenirholidays.com');
INSERT INTO `tf_meta` VALUES ('company_name','Avenir Holidays (Pvt) Ltd');
INSERT INTO `tf_meta` VALUES ('company_phone','(+94) 114 215 977');
INSERT INTO `tf_meta` VALUES ('crew_voucher_next','1003');
INSERT INTO `tf_meta` VALUES ('crew_voucher_prefix','CPV');
INSERT INTO `tf_meta` VALUES ('dates_rule_backfilled','4');
INSERT INTO `tf_meta` VALUES ('desks_version','9');
INSERT INTO `tf_meta` VALUES ('driver_advance_next','1004');
INSERT INTO `tf_meta` VALUES ('driver_advance_pcts','20, 30, 40, 50');
INSERT INTO `tf_meta` VALUES ('driver_advance_prefix','DAV');
INSERT INTO `tf_meta` VALUES ('driver_settlement_next','1001');
INSERT INTO `tf_meta` VALUES ('driver_settlement_prefix','DST');
INSERT INTO `tf_meta` VALUES ('extract_model','claude-sonnet-5');
INSERT INTO `tf_meta` VALUES ('extract_provider','anthropic');
INSERT INTO `tf_meta` VALUES ('free_visits_backfilled','6');
INSERT INTO `tf_meta` VALUES ('free_visits_unplaced','6');
INSERT INTO `tf_meta` VALUES ('gateway','Katunayake');
INSERT INTO `tf_meta` VALUES ('gateway_out','Katunayake');
INSERT INTO `tf_meta` VALUES ('hotel_auto_send','0');
INSERT INTO `tf_meta` VALUES ('inquiry_next','1616');
INSERT INTO `tf_meta` VALUES ('inquiry_prefix','INQ');
INSERT INTO `tf_meta` VALUES ('itin_airlines','UL = SriLankan Airlines\nEK = Emirates\nQR = Qatar Airways\nEY = Etihad Airways\nSQ = Singapore Airlines\nMH = Malaysia Airlines\nCX = Cathay Pacific\nAI = Air India\n6E = IndiGo\nTK = Turkish Airlines\nBA = British Airways\nLH = Lufthansa\nAF = Air France\nKL = KLM\nSU = Aeroflot\nTG = Thai Airways\nFZ = flydubai\nG9 = Air Arabia\nWY = Oman Air\nGF = Gulf Air\nSV = Saudia\nKU = Kuwait Airways\nJL = Japan Airlines\nNH = ANA\nKE = Korean Air\nOZ = Asiana Airlines\nCZ = China Southern\nCA = Air China\nMU = China Eastern\nUK = Vistara\n8D = FitsAir\nVJ = VietJet Air\nAK = AirAsia\nD7 = AirAsia X\nOD = Batik Air\nJ2 = Azerbaijan Airlines\nPG = Bangkok Airways');
INSERT INTO `tf_meta` VALUES ('itin_airport','Bandaranaike International Airport');
INSERT INTO `tf_meta` VALUES ('itin_arrival','Upon arrival at {airport}{flight}, you will be met and assisted by {representative}. Thereafter, transfer to your hotel{in_area}.');
INSERT INTO `tf_meta` VALUES ('itin_arrival_visits','En route, {visits}.');
INSERT INTO `tf_meta` VALUES ('itin_checkin','Check in to {hotel} and spend the remainder of the day at leisure. | On arrival, check in to {hotel}; the rest of the day is at leisure.');
INSERT INTO `tf_meta` VALUES ('itin_departure','Transfer to {airport} for your departure flight{to}{flight}.');
INSERT INTO `tf_meta` VALUES ('itin_departure_visits','{morning}, {visits}. Thereafter, transfer to {airport} for your departure flight{to}{flight}.');
INSERT INTO `tf_meta` VALUES ('itin_enroute','En route, {visits}. Thereafter, continue to {area}.');
INSERT INTO `tf_meta` VALUES ('itin_flight_in',' on {airline} flight {number}');
INSERT INTO `tf_meta` VALUES ('itin_flight_in_raw',' on {flight}');
INSERT INTO `tf_meta` VALUES ('itin_flight_out',' aboard {airline} {number}');
INSERT INTO `tf_meta` VALUES ('itin_flight_out_raw',' aboard {flight}');
INSERT INTO `tf_meta` VALUES ('itin_meal_dinner','Dinner at {place}.');
INSERT INTO `tf_meta` VALUES ('itin_meal_lunch','Lunch at {place}.');
INSERT INTO `tf_meta` VALUES ('itin_morning','After breakfast');
INSERT INTO `tf_meta` VALUES ('itin_morning_no_bf','In the morning');
INSERT INTO `tf_meta` VALUES ('itin_move','{morning}, leave for {area}. | {morning}, proceed to {area}.');
INSERT INTO `tf_meta` VALUES ('itin_move_before','{morning}, {visits}. Thereafter, leave for {area}. | {morning}, {visits}, then proceed to {area}.');
INSERT INTO `tf_meta` VALUES ('itin_on_arrival','Upon arrival, {visits} before proceeding to the hotel. | Upon arrival in {area}, {visits}. Thereafter, proceed to the hotel.');
INSERT INTO `tf_meta` VALUES ('itin_representative','our local representative');
INSERT INTO `tf_meta` VALUES ('itin_same_area','{morning}, transfer to {hotel} and check in. The remainder of the day is at leisure.');
INSERT INTO `tf_meta` VALUES ('itin_same_area_visits','{morning}, {visits}. Thereafter, transfer to {hotel} and check in.');
INSERT INTO `tf_meta` VALUES ('itin_stay','{morning}, {visits}. Upon completion of the sightseeing programme, return to the hotel. | {morning}, {visits}. Thereafter, return to the hotel and spend the remainder of the day at leisure.');
INSERT INTO `tf_meta` VALUES ('itin_stay_free','Day at leisure{in_area}. | Spend the day at leisure{in_area}.');
INSERT INTO `tf_meta` VALUES ('km_min_samples','3');
INSERT INTO `tf_meta` VALUES ('km_sane_max','450');
INSERT INTO `tf_meta` VALUES ('mail_send_via','staff');
INSERT INTO `tf_meta` VALUES ('markup_default_pct','');
INSERT INTO `tf_meta` VALUES ('meal_auto_lunch','1');
INSERT INTO `tf_meta` VALUES ('meal_dinner_time','19:30');
INSERT INTO `tf_meta` VALUES ('meal_hb_default','dinner');
INSERT INTO `tf_meta` VALUES ('meal_lunch_time','12:30');
INSERT INTO `tf_meta` VALUES ('meal_supplier_cats','4,1');
INSERT INTO `tf_meta` VALUES ('meal_voucher_next','1001');
INSERT INTO `tf_meta` VALUES ('meal_voucher_prefix','RBV');
INSERT INTO `tf_meta` VALUES ('mileage_quote_source','google');
INSERT INTO `tf_meta` VALUES ('pack_warn_days','7');
INSERT INTO `tf_meta` VALUES ('photo_folder','uploads/tour_photos');
INSERT INTO `tf_meta` VALUES ('photo_max_width','1600');
INSERT INTO `tf_meta` VALUES ('photo_thumb_width','480');
INSERT INTO `tf_meta` VALUES ('place_import_done','2026-10-03 14:22:57');
INSERT INTO `tf_meta` VALUES ('position_on_arrival','1');
INSERT INTO `tf_meta` VALUES ('quote_currency','USD');
INSERT INTO `tf_meta` VALUES ('quote_min_chars','8');
INSERT INTO `tf_meta` VALUES ('quote_plan_types','contract,migrated,addendum,series');
INSERT INTO `tf_meta` VALUES ('quote_terms','Rates are quoted per person in US dollars on the room basis shown, valid until the date given, and subject to availability at the time of confirmation. Entrance fees, meals and services not listed are excluded.');
INSERT INTO `tf_meta` VALUES ('quote_validity_days','14');
INSERT INTO `tf_meta` VALUES ('schema_version','46');
INSERT INTO `tf_meta` VALUES ('service_aliases','temple of the tooth => KDY ENT\ntemple of tooth => KDY ENT\ntooth relic => KDY ENT\nkandy temple => KDY ENT');
INSERT INTO `tf_meta` VALUES ('service_no_charge_words','s/s, ss, sightseeing, sight seeing, city tour, market, shopping, photo stop, drive through, free time, leisure, at leisure');
INSERT INTO `tf_meta` VALUES ('tgs_menu_before','{\"master_files/transport_rates\":{\"id\":223,\"cat_id\":4,\"order_id\":24,\"name\":\"Transport Rates\"},\"master_files/mileage_allowances\":{\"id\":224,\"cat_id\":4,\"order_id\":25,\"name\":\"Mileage Allowances\"},\"master_files/crew_people\":{\"id\":238,\"cat_id\":4,\"order_id\":31,\"name\":\"Drivers & Guides\"},\"master_files/crew_bata\":{\"id\":232,\"cat_id\":4,\"order_id\":27,\"name\":\"Crew Bata\"},\"master_files/crew_people/bank\":{\"id\":246,\"cat_id\":4,\"order_id\":38,\"name\":\"Driver & Guide bank details\"}}');
INSERT INTO `tf_meta` VALUES ('tour_next','54');
INSERT INTO `tf_meta` VALUES ('tour_pad','5');
INSERT INTO `tf_meta` VALUES ('tour_prefix','AVH');
INSERT INTO `tf_meta` VALUES ('tour_year','2026');
INSERT INTO `tf_meta` VALUES ('vehicle_base','Colombo');
INSERT INTO `tf_meta` VALUES ('voucher_next','1057');
INSERT INTO `tf_meta` VALUES ('voucher_prefix','HV');
/*!40000 ALTER TABLE `tf_meta` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_mileage_allowance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_mileage_allowance` (
  `code` varchar(30) NOT NULL,
  `name` varchar(80) NOT NULL,
  `km` decimal(10,2) NOT NULL DEFAULT 0.00,
  `applies` enum('stay_day','tour_start','tour_end','both_ends') NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `updated_by` varchar(100) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_mileage_allowance` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_mileage_allowance` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_pax_rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_pax_rooms` (
  `pax` smallint(5) unsigned NOT NULL,
  `rooms_sgl` smallint(5) unsigned NOT NULL DEFAULT 0,
  `rooms_dbl` smallint(5) unsigned NOT NULL DEFAULT 0,
  `rooms_twn` smallint(5) unsigned NOT NULL DEFAULT 0,
  `rooms_tpl` smallint(5) unsigned NOT NULL DEFAULT 0,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`pax`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_pax_rooms` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_pax_rooms` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_photo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_photo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `file` varchar(190) NOT NULL,
  `thumb` varchar(190) DEFAULT NULL,
  `original_name` varchar(190) DEFAULT NULL,
  `mime` varchar(60) DEFAULT NULL,
  `width` smallint(5) unsigned DEFAULT NULL,
  `height` smallint(5) unsigned DEFAULT NULL,
  `bytes` int(10) unsigned DEFAULT NULL,
  `caption` varchar(190) DEFAULT NULL,
  `credit` varchar(120) DEFAULT NULL,
  `preferred` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `uses` int(10) unsigned NOT NULL DEFAULT 0,
  `last_used_at` datetime DEFAULT NULL,
  `uploaded_at` datetime NOT NULL,
  `uploaded_by` varchar(100) DEFAULT NULL,
  `replaced_at` datetime DEFAULT NULL,
  `replaced_by` varchar(100) DEFAULT NULL,
  `removed_at` datetime DEFAULT NULL,
  `removed_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_photo_active` (`active`,`preferred`,`uses`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_photo` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_photo` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_photo_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_photo_tag` (
  `code` varchar(40) NOT NULL,
  `name` varchar(120) NOT NULL,
  `kind` enum('destination','attraction','activity','general') NOT NULL DEFAULT 'destination',
  `keywords` varchar(1000) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_photo_tag` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_photo_tag` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_photo_tag_map`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_photo_tag_map` (
  `photo_id` int(10) unsigned NOT NULL,
  `tag_code` varchar(40) NOT NULL,
  PRIMARY KEY (`photo_id`,`tag_code`),
  KEY `ix_tf_ptm_tag` (`tag_code`),
  CONSTRAINT `fk_tf_ptm_photo` FOREIGN KEY (`photo_id`) REFERENCES `tf_photo` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_photo_tag_map` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_photo_tag_map` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_place`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_place` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(60) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `destination` varchar(120) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `map_link` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `category` varchar(40) DEFAULT NULL,
  `pay_mode` enum('in_tour','prepaid','direct') NOT NULL DEFAULT 'in_tour',
  `jeep` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `merged_into` int(10) unsigned DEFAULT NULL,
  `review_note` varchar(1000) DEFAULT NULL,
  `source` enum('import','manual') NOT NULL DEFAULT 'manual',
  `created_at` datetime DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_place_name` (`name`(80)),
  KEY `ix_tf_place_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_place` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_place` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_place_alias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_place_alias` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `place_id` int(10) unsigned NOT NULL,
  `alias` varchar(190) NOT NULL,
  `alias_key` varchar(190) NOT NULL,
  `source` enum('manual','import') NOT NULL DEFAULT 'manual',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_place_alias_place` (`place_id`),
  KEY `ix_tf_place_alias_key` (`alias_key`,`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_place_alias` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_place_alias` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_place_import`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_place_import` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `source` enum('old_list','excursion') NOT NULL,
  `source_id` varchar(100) NOT NULL,
  `outcome` enum('imported','merged','split','sightseeing','review','service','not_listed') NOT NULL,
  `place_id` int(10) unsigned DEFAULT NULL,
  `old_name` varchar(255) DEFAULT NULL,
  `old_code` varchar(100) DEFAULT NULL,
  `old_figure` varchar(60) DEFAULT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `imported_by` varchar(100) DEFAULT NULL,
  `imported_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_tf_place_import` (`source`,`source_id`),
  KEY `ix_tf_place_import_place` (`place_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_place_import` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_place_import` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_place_rate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_place_rate` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `place_id` int(10) unsigned NOT NULL,
  `charge` enum('entrance','jeep') NOT NULL DEFAULT 'entrance',
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL,
  `free_entry` tinyint(1) NOT NULL DEFAULT 0,
  `adult_rate` decimal(12,2) NOT NULL DEFAULT 0.00,
  `child_rate` decimal(12,2) DEFAULT NULL,
  `currency` char(3) NOT NULL DEFAULT 'USD',
  `note` varchar(255) DEFAULT NULL,
  `source` enum('manual','import') NOT NULL DEFAULT 'manual',
  `source_ref` varchar(100) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_place_rate` (`place_id`,`charge`,`active`,`valid_from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_place_rate` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_place_rate` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_quote`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_quote` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `intake_id` int(10) unsigned NOT NULL,
  `version` smallint(5) unsigned NOT NULL DEFAULT 1,
  `status` enum('draft','issued','superseded') NOT NULL DEFAULT 'draft',
  `currency` char(3) NOT NULL DEFAULT 'USD',
  `usd_rate` decimal(10,4) DEFAULT NULL,
  `rate_date` date DEFAULT NULL,
  `pax` smallint(5) unsigned DEFAULT NULL,
  `cost_lkr` decimal(14,2) DEFAULT NULL,
  `cost_usd` decimal(14,2) DEFAULT NULL,
  `markup_pct` decimal(6,2) DEFAULT NULL,
  `sell_usd` decimal(14,2) DEFAULT NULL,
  `per_person_usd` decimal(12,2) DEFAULT NULL,
  `cost_json` mediumtext DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `note` text DEFAULT NULL,
  `issued_at` datetime DEFAULT NULL,
  `issued_by` varchar(100) DEFAULT NULL,
  `max_pax` smallint(5) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tf_quote_v` (`intake_id`,`version`),
  CONSTRAINT `fk_tf_quote_intake` FOREIGN KEY (`intake_id`) REFERENCES `tf_intake` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_quote` DISABLE KEYS */;
INSERT INTO `tf_quote` VALUES (1,1,1,'issued','USD',NULL,NULL,4,NULL,NULL,NULL,0.00,0.00,NULL,NULL,NULL,'2026-10-08 13:49:16','mailtestadmin',NULL,'2026-10-08 13:49:16','mailtestadmin');
/*!40000 ALTER TABLE `tf_quote` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_quote_email`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_quote_email` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `intake_id` int(10) unsigned NOT NULL,
  `quote_id` int(10) unsigned NOT NULL,
  `version` smallint(5) unsigned NOT NULL,
  `to_email` varchar(190) NOT NULL,
  `cc_email` varchar(500) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `pdf_name` varchar(120) DEFAULT NULL,
  `max_pax` smallint(5) unsigned DEFAULT NULL,
  `status` enum('sent','failed') NOT NULL DEFAULT 'sent',
  `error` varchar(500) DEFAULT NULL,
  `sent_by` varchar(100) DEFAULT NULL,
  `sent_by_id` int(10) unsigned DEFAULT NULL,
  `sent_name` varchar(190) DEFAULT NULL,
  `sent_from` varchar(190) DEFAULT NULL,
  `sent_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_qe_quote` (`quote_id`,`sent_at`),
  KEY `ix_tf_qe_intake` (`intake_id`,`sent_at`),
  CONSTRAINT `fk_tf_qe_intake` FOREIGN KEY (`intake_id`) REFERENCES `tf_intake` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tf_qe_quote` FOREIGN KEY (`quote_id`) REFERENCES `tf_quote` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_quote_email` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_quote_email` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_reason`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_reason` (
  `code` varchar(30) NOT NULL,
  `kind` enum('hotel_problem','service_problem') NOT NULL DEFAULT 'hotel_problem',
  `name` varchar(120) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_reason` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_reason` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_service_job`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_service_job` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tour_id` int(10) unsigned NOT NULL,
  `seq` smallint(5) unsigned NOT NULL DEFAULT 0,
  `source` enum('service','excursion','desk') NOT NULL DEFAULT 'service',
  `source_id` int(10) unsigned DEFAULT NULL,
  `kind` varchar(20) NOT NULL DEFAULT 'other',
  `title` varchar(255) NOT NULL,
  `day_no` smallint(5) unsigned DEFAULT NULL,
  `on_date` date DEFAULT NULL,
  `pax` smallint(5) unsigned DEFAULT NULL,
  `qty` decimal(10,2) DEFAULT NULL,
  `pay_mode` varchar(20) DEFAULT NULL,
  `fee_id` int(11) DEFAULT NULL,
  `fee_code` varchar(100) DEFAULT NULL,
  `place_id` int(10) unsigned DEFAULT NULL,
  `special_note` varchar(500) DEFAULT NULL,
  `status` enum('to_book','requested','confirmed','not_needed','problem','cancelled','pay_at_gate') NOT NULL DEFAULT 'to_book',
  `requested_at` datetime DEFAULT NULL,
  `requested_by` varchar(100) DEFAULT NULL,
  `request_note` varchar(400) DEFAULT NULL,
  `confirmation_ref` varchar(100) DEFAULT NULL,
  `confirmed_with` varchar(160) DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `confirmed_by` varchar(100) DEFAULT NULL,
  `problem_code` varchar(30) DEFAULT NULL,
  `problem_note` varchar(400) DEFAULT NULL,
  `problem_at` datetime DEFAULT NULL,
  `problem_by` varchar(100) DEFAULT NULL,
  `amended` tinyint(1) NOT NULL DEFAULT 0,
  `note` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `archived_at` datetime DEFAULT NULL,
  `archived_by` varchar(100) DEFAULT NULL,
  `archive_note` varchar(400) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_sjob_tour` (`tour_id`,`seq`),
  KEY `ix_tf_sjob_status` (`status`,`on_date`),
  CONSTRAINT `fk_tf_sjob_tour` FOREIGN KEY (`tour_id`) REFERENCES `tf_tour` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_service_job` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_service_job` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_ticket`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_ticket` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `attraction_id` int(10) unsigned NOT NULL,
  `batch_id` int(10) unsigned DEFAULT NULL,
  `ticket_no` varchar(60) NOT NULL,
  `sort_no` bigint(20) unsigned DEFAULT NULL,
  `status` enum('available','allocated','used','void') NOT NULL DEFAULT 'available',
  `tour_id` int(10) unsigned DEFAULT NULL,
  `service_job_id` int(10) unsigned DEFAULT NULL,
  `allocated_at` datetime DEFAULT NULL,
  `allocated_by` varchar(100) DEFAULT NULL,
  `used_at` datetime DEFAULT NULL,
  `used_by` varchar(100) DEFAULT NULL,
  `void_at` datetime DEFAULT NULL,
  `void_by` varchar(100) DEFAULT NULL,
  `void_note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_tf_ticket_no` (`attraction_id`,`ticket_no`),
  KEY `ix_tf_ticket_pick` (`attraction_id`,`status`,`sort_no`),
  KEY `ix_tf_ticket_tour` (`tour_id`),
  KEY `ix_tf_ticket_job` (`service_job_id`),
  CONSTRAINT `fk_tf_ticket_att` FOREIGN KEY (`attraction_id`) REFERENCES `tf_ticket_attraction` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_ticket` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_ticket` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_ticket_attraction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_ticket_attraction` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fee_id` int(11) DEFAULT NULL,
  `place_id` int(10) unsigned DEFAULT NULL,
  `fee_code` varchar(100) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `control` enum('stock','pay_at_gate') NOT NULL DEFAULT 'stock',
  `low_mark` smallint(5) unsigned DEFAULT NULL,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `currency` char(3) DEFAULT NULL,
  `note` varchar(500) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_tf_tatt_fee` (`fee_id`),
  UNIQUE KEY `ux_tf_tatt_place` (`place_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_ticket_attraction` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_ticket_attraction` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_ticket_batch`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_ticket_batch` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `attraction_id` int(10) unsigned NOT NULL,
  `received_on` date NOT NULL,
  `paid_on` date DEFAULT NULL,
  `prefix` varchar(20) DEFAULT NULL,
  `from_no` varchar(60) NOT NULL,
  `to_no` varchar(60) NOT NULL,
  `count` smallint(5) unsigned NOT NULL,
  `amount_paid` decimal(12,2) DEFAULT NULL,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `currency` char(3) DEFAULT NULL,
  `ref` varchar(100) DEFAULT NULL,
  `note` varchar(400) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_tbatch_att` (`attraction_id`,`received_on`),
  CONSTRAINT `fk_tf_tbatch_att` FOREIGN KEY (`attraction_id`) REFERENCES `tf_ticket_attraction` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_ticket_batch` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_ticket_batch` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_tour`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_tour` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tour_no` varchar(20) NOT NULL,
  `intake_id` int(10) unsigned NOT NULL,
  `stage` enum('confirmed','operating','completed','closed','cancelled') NOT NULL DEFAULT 'confirmed',
  `amending` tinyint(1) NOT NULL DEFAULT 0,
  `handler_id` int(10) unsigned DEFAULT NULL,
  `handler_name` varchar(190) DEFAULT NULL,
  `agent_acc` varchar(50) DEFAULT NULL,
  `agent_name` varchar(190) DEFAULT NULL,
  `tour_name` varchar(190) DEFAULT NULL,
  `market_code` varchar(10) DEFAULT NULL,
  `country_iso` char(2) DEFAULT NULL,
  `arrival_date` date DEFAULT NULL,
  `departure_date` date DEFAULT NULL,
  `nights` smallint(5) unsigned DEFAULT NULL,
  `pax_total` smallint(5) unsigned DEFAULT NULL,
  `pax_adults` smallint(5) unsigned DEFAULT NULL,
  `pax_children` smallint(5) unsigned DEFAULT NULL,
  `rooms_note` varchar(255) DEFAULT NULL,
  `hotel_class` varchar(60) DEFAULT NULL,
  `arrival_flight` varchar(160) DEFAULT NULL,
  `departure_flight` varchar(160) DEFAULT NULL,
  `confirmed_at` datetime NOT NULL,
  `confirmed_by` varchar(100) DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancelled_by` varchar(100) DEFAULT NULL,
  `cancel_why` varchar(400) DEFAULT NULL,
  `legacy_tour_id` varchar(50) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `quote_id` int(10) unsigned DEFAULT NULL,
  `quote_version` smallint(5) unsigned DEFAULT NULL,
  `reservations_confirmed_at` datetime DEFAULT NULL,
  `reservations_confirmed_by` varchar(100) DEFAULT NULL,
  `transport_complete_at` datetime DEFAULT NULL,
  `transport_complete_by` varchar(100) DEFAULT NULL,
  `archived_at` datetime DEFAULT NULL,
  `archived_by` varchar(100) DEFAULT NULL,
  `archive_note` varchar(400) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tf_tour_no` (`tour_no`),
  UNIQUE KEY `uq_tf_tour_intake` (`intake_id`),
  KEY `ix_tf_tour_stage` (`stage`,`arrival_date`),
  CONSTRAINT `fk_tf_tour_intake` FOREIGN KEY (`intake_id`) REFERENCES `tf_intake` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_tour` DISABLE KEYS */;
INSERT INTO `tf_tour` VALUES (1,'TEST-MAIL-1008',1,'confirmed',0,1,'TEST Mail Administrator',NULL,'TEST AGENT (dummy)','TEST — e-mail workflow test, not a real booking',NULL,NULL,'2026-12-10','2026-12-12',2,4,4,0,NULL,NULL,NULL,NULL,'2026-10-08 13:49:16','mailtestadmin',NULL,NULL,NULL,NULL,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-08 13:49:16','2026-10-08 13:49:16','mailtestadmin');
/*!40000 ALTER TABLE `tf_tour` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_tour_event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_tour_event` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `intake_id` int(10) unsigned NOT NULL,
  `tour_id` int(10) unsigned DEFAULT NULL,
  `lane` enum('tour','quotation','hotel','transport','service') NOT NULL DEFAULT 'tour',
  `job_id` int(10) unsigned DEFAULT NULL,
  `event` varchar(40) NOT NULL,
  `from_status` varchar(30) DEFAULT NULL,
  `to_status` varchar(30) DEFAULT NULL,
  `note` varchar(500) DEFAULT NULL,
  `by` varchar(100) DEFAULT NULL,
  `at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_event_tour` (`tour_id`,`id`),
  KEY `ix_tf_event_intake` (`intake_id`,`id`),
  CONSTRAINT `fk_tf_event_tour` FOREIGN KEY (`tour_id`) REFERENCES `tf_tour` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_tour_event` DISABLE KEYS */;
INSERT INTO `tf_tour_event` VALUES (1,1,1,'quotation',NULL,'confirmed','working','confirmed','Tour AVH-2026-00053: one job to Hotel Bookings (1 hotel stay(s), 0 activity/ticket line(s)) and one to Transport (0 vehicle(s)). Sold as version 1.','mailtestadmin','2026-10-08 13:49:16');
/*!40000 ALTER TABLE `tf_tour_event` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_transport_crew`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_transport_crew` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` int(10) unsigned NOT NULL,
  `role_code` varchar(20) NOT NULL,
  `role_name` varchar(80) NOT NULL,
  `kind` enum('driver','guide') NOT NULL,
  `language` varchar(40) DEFAULT NULL,
  `days` smallint(5) unsigned DEFAULT NULL,
  `bata` decimal(10,2) DEFAULT NULL,
  `currency` char(3) NOT NULL DEFAULT 'LKR',
  `amount` decimal(12,2) DEFAULT NULL,
  `supplier_acc` varchar(50) DEFAULT NULL,
  `person_id` int(10) unsigned DEFAULT NULL,
  `person_name` varchar(190) DEFAULT NULL,
  `person_phone` varchar(60) DEFAULT NULL,
  `person_bata` decimal(10,2) DEFAULT NULL,
  `person_category` varchar(10) DEFAULT NULL,
  `person_km_rate` decimal(10,2) DEFAULT NULL,
  `person_rate_from` date DEFAULT NULL,
  `person_rate_source` varchar(10) DEFAULT NULL,
  `person_rate_class` varchar(20) DEFAULT NULL,
  `person_bata_from` date DEFAULT NULL,
  `person_overnight` decimal(10,2) DEFAULT NULL,
  `person_bata_source` varchar(10) DEFAULT NULL,
  `person_overnight_from` date DEFAULT NULL,
  `person_overnight_source` varchar(10) DEFAULT NULL,
  `status` enum('to_engage','engaged','declined','replaced') NOT NULL DEFAULT 'to_engage',
  `note` varchar(255) DEFAULT NULL,
  `date_from` date DEFAULT NULL,
  `date_to` date DEFAULT NULL,
  `replaces` int(10) unsigned DEFAULT NULL,
  `replaced_by` int(10) unsigned DEFAULT NULL,
  `replaced_at` datetime DEFAULT NULL,
  `replaced_note` varchar(400) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_tcrew_job` (`job_id`),
  CONSTRAINT `fk_tf_tcrew_job` FOREIGN KEY (`job_id`) REFERENCES `tf_transport_job` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_transport_crew` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_transport_crew` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_transport_doc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_transport_doc` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` int(10) unsigned NOT NULL,
  `doc_code` varchar(30) NOT NULL,
  `doc_name` varchar(80) NOT NULL,
  `phase` enum('before','after') NOT NULL DEFAULT 'before',
  `state` enum('pending','issued','returned','accepted','waived') NOT NULL DEFAULT 'pending',
  `at` datetime DEFAULT NULL,
  `by` varchar(100) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tf_tdoc` (`job_id`,`doc_code`),
  CONSTRAINT `fk_tf_tdoc_job` FOREIGN KEY (`job_id`) REFERENCES `tf_transport_job` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_transport_doc` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_transport_doc` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_transport_job`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_transport_job` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tour_id` int(10) unsigned NOT NULL,
  `seq` smallint(5) unsigned NOT NULL DEFAULT 0,
  `intake_vehicle_id` int(10) unsigned DEFAULT NULL,
  `class_code` varchar(20) DEFAULT NULL,
  `vehicle_type` varchar(120) NOT NULL,
  `seats` smallint(5) unsigned DEFAULT NULL,
  `qty` smallint(5) unsigned NOT NULL DEFAULT 1,
  `days` smallint(5) unsigned DEFAULT NULL,
  `km_quoted` decimal(10,2) DEFAULT NULL,
  `extra_km_quoted` decimal(10,2) DEFAULT NULL,
  `km_total` decimal(10,2) DEFAULT NULL,
  `extra_km_total` decimal(10,2) DEFAULT NULL,
  `actual_km_total` decimal(10,2) DEFAULT NULL,
  `km_rate` decimal(10,2) DEFAULT NULL,
  `rate_id` int(10) unsigned DEFAULT NULL,
  `currency` char(3) NOT NULL DEFAULT 'LKR',
  `vehicle_id` int(11) DEFAULT NULL,
  `vehicle_reg` varchar(60) DEFAULT NULL,
  `vehicle_note` varchar(255) DEFAULT NULL,
  `status` enum('to_allocate','allocated','scheduled','pack_issued','running','returned','settled','cancelled') NOT NULL DEFAULT 'to_allocate',
  `allocated_at` datetime DEFAULT NULL,
  `allocated_by` varchar(100) DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `scheduled_by` varchar(100) DEFAULT NULL,
  `pack_at` datetime DEFAULT NULL,
  `pack_by` varchar(100) DEFAULT NULL,
  `running_at` datetime DEFAULT NULL,
  `returned_at` datetime DEFAULT NULL,
  `returned_by` varchar(100) DEFAULT NULL,
  `settled_at` datetime DEFAULT NULL,
  `settled_by` varchar(100) DEFAULT NULL,
  `amended` tinyint(1) NOT NULL DEFAULT 0,
  `note` varchar(500) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `archived_at` datetime DEFAULT NULL,
  `archived_by` varchar(100) DEFAULT NULL,
  `archive_note` varchar(400) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_tjob_tour` (`tour_id`,`seq`),
  KEY `ix_tf_tjob_status` (`status`),
  CONSTRAINT `fk_tf_tjob_tour` FOREIGN KEY (`tour_id`) REFERENCES `tf_tour` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_transport_job` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_transport_job` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_transport_leg`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_transport_leg` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` int(10) unsigned NOT NULL,
  `day_no` smallint(5) unsigned NOT NULL DEFAULT 0,
  `day_date` date DEFAULT NULL,
  `seq` smallint(5) unsigned NOT NULL DEFAULT 0,
  `from_place` varchar(190) DEFAULT NULL,
  `to_place` varchar(190) DEFAULT NULL,
  `km` decimal(10,2) DEFAULT NULL,
  `extra_km` decimal(10,2) DEFAULT NULL,
  `google_km` decimal(10,2) DEFAULT NULL,
  `quoted_by` varchar(16) DEFAULT NULL,
  `actual_km` decimal(10,2) DEFAULT NULL,
  `actual_by` varchar(100) DEFAULT NULL,
  `actual_at` datetime DEFAULT NULL,
  `actual_note` varchar(255) DEFAULT NULL,
  `kind` enum('transfer','local','arrival','departure','positioning','idle','garage') NOT NULL DEFAULT 'transfer',
  `night_hotel` varchar(190) DEFAULT NULL,
  `night_area` varchar(160) DEFAULT NULL,
  `origin` enum('costed','changed','added') NOT NULL DEFAULT 'costed',
  `note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_tleg_job` (`job_id`,`day_no`,`seq`),
  CONSTRAINT `fk_tf_tleg_job` FOREIGN KEY (`job_id`) REFERENCES `tf_transport_job` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_transport_leg` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_transport_leg` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_transport_rate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_transport_rate` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `class_code` varchar(20) NOT NULL,
  `rate_km` decimal(10,2) NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'LKR',
  `valid_from` date NOT NULL,
  `valid_to` date DEFAULT NULL,
  `reason` varchar(200) DEFAULT NULL,
  `set_by` varchar(100) DEFAULT NULL,
  `set_at` datetime DEFAULT NULL,
  `corrected_from` decimal(10,2) DEFAULT NULL,
  `corrected_by` varchar(100) DEFAULT NULL,
  `corrected_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_trate_class` (`class_code`,`valid_from`),
  CONSTRAINT `fk_tf_trate_class` FOREIGN KEY (`class_code`) REFERENCES `tf_vehicle_class` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_transport_rate` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_transport_rate` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_user_authority`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_user_authority` (
  `user_id` int(10) unsigned NOT NULL,
  `authority` varchar(40) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `name` varchar(160) DEFAULT NULL,
  `granted` tinyint(1) NOT NULL DEFAULT 0,
  `set_by` varchar(100) DEFAULT NULL,
  `set_by_id` int(10) unsigned DEFAULT NULL,
  `set_at` datetime DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`user_id`,`authority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_user_authority` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_user_authority` ENABLE KEYS */;
DROP TABLE IF EXISTS `tf_vehicle_class`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tf_vehicle_class` (
  `code` varchar(20) NOT NULL,
  `name` varchar(80) NOT NULL,
  `max_pax` smallint(5) unsigned NOT NULL,
  `seats` smallint(5) unsigned DEFAULT NULL,
  `match_words` varchar(160) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`code`),
  KEY `ix_tf_vclass_pax` (`max_pax`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tf_vehicle_class` DISABLE KEYS */;
/*!40000 ALTER TABLE `tf_vehicle_class` ENABLE KEYS */;
DROP TABLE IF EXISTS `tour_activity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tour_activity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tour_id` int(11) DEFAULT NULL,
  `tour_row` int(11) DEFAULT NULL,
  `tour_date` date DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  `fee` decimal(10,0) DEFAULT NULL,
  `ref_no` int(11) DEFAULT NULL,
  `supplier_id` varchar(255) DEFAULT NULL,
  `supplier_name` varchar(255) DEFAULT NULL,
  `payment_request` tinyint(1) NOT NULL DEFAULT 0,
  `payment_request_user` varchar(100) DEFAULT NULL,
  `payment_request_date` date DEFAULT NULL,
  `lkr_fee` decimal(10,2) DEFAULT NULL,
  `is_prepaid` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tour_activity` DISABLE KEYS */;
/*!40000 ALTER TABLE `tour_activity` ENABLE KEYS */;
DROP TABLE IF EXISTS `tour_finalize`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tour_finalize` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tour_id` varchar(100) NOT NULL,
  `manual_id` varchar(100) DEFAULT NULL,
  `consignee_name` varchar(255) DEFAULT NULL,
  `driver_acc` varchar(100) NOT NULL,
  `driver_name` varchar(255) DEFAULT NULL,
  `tour_date_ids` longtext DEFAULT NULL,
  `expense_ids` longtext DEFAULT NULL,
  `expense_count` int(10) unsigned NOT NULL DEFAULT 0,
  `distance_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_dollar_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_lkr_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `finalized_by` varchar(100) DEFAULT NULL,
  `finalized_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_tour_finalize_tour_id` (`tour_id`) USING BTREE,
  KEY `idx_tour_finalize_driver_acc` (`driver_acc`) USING BTREE,
  KEY `idx_tour_finalize_status` (`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tour_finalize` DISABLE KEYS */;
/*!40000 ALTER TABLE `tour_finalize` ENABLE KEYS */;
DROP TABLE IF EXISTS `tour_itinerary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tour_itinerary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tour_id` int(11) DEFAULT NULL,
  `tour_row` int(11) DEFAULT NULL,
  `tour_date` date DEFAULT NULL,
  `order_id` float DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `area` varchar(255) DEFAULT NULL,
  `bf` varchar(255) DEFAULT NULL,
  `lunch` varchar(255) DEFAULT NULL,
  `dinner` varchar(255) DEFAULT NULL,
  `insert_date` date DEFAULT NULL,
  `ref_no` int(11) DEFAULT NULL,
  `loc_ref` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tour_itinerary` DISABLE KEYS */;
/*!40000 ALTER TABLE `tour_itinerary` ENABLE KEYS */;
DROP TABLE IF EXISTS `tour_quotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tour_quotations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quotation_id` varchar(255) DEFAULT NULL,
  `quotation_day` decimal(10,1) DEFAULT NULL,
  `hotel_code` varchar(255) DEFAULT NULL,
  `meal_code` varchar(255) DEFAULT NULL,
  `hotel_acc` varchar(255) DEFAULT NULL,
  `hotel_name` varchar(255) DEFAULT NULL,
  `room_type` varchar(255) DEFAULT NULL,
  `room_type_id` int(11) DEFAULT NULL,
  `tour_date` date DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `sgl` decimal(10,2) DEFAULT NULL,
  `dbl` decimal(10,2) DEFAULT NULL,
  `tpl` decimal(10,2) DEFAULT NULL,
  `created_time` datetime DEFAULT NULL,
  `created_user` varchar(255) DEFAULT NULL,
  `distance_type` int(11) DEFAULT NULL,
  `det_flag` int(11) DEFAULT 0,
  `transfer_code` varchar(255) DEFAULT NULL,
  `transfer` varchar(255) DEFAULT NULL,
  `distance` double DEFAULT NULL,
  `from_location` varchar(255) DEFAULT NULL,
  `to_location` varchar(255) DEFAULT NULL,
  `gdistance` double DEFAULT NULL,
  `added_by` varchar(255) DEFAULT NULL,
  `added_time` datetime DEFAULT NULL,
  `trans_flag` int(11) DEFAULT 0,
  `confirm` int(11) DEFAULT 0,
  `finalize` int(11) DEFAULT 0,
  `hotel_advance` decimal(10,2) DEFAULT 0.00,
  `advance_ref` varchar(255) DEFAULT NULL,
  `extra_kilo` double DEFAULT NULL,
  `extra_act_fee` decimal(10,2) DEFAULT 0.00,
  `sglcount` int(11) DEFAULT 0,
  `dblcount` int(11) DEFAULT 0,
  `tplcount` int(11) DEFAULT NULL,
  `hotel_booking_flag` int(11) DEFAULT 0,
  `booking_confirmed_flag` int(11) DEFAULT 0,
  `hotel_payment_flag` int(11) DEFAULT 0,
  `extra_hotel_fee` decimal(10,2) DEFAULT 0.00,
  `hotel_dollar_rate` decimal(10,2) DEFAULT 0.00,
  `confirmation_email` varchar(255) DEFAULT NULL,
  `confirmation_reference` varchar(255) DEFAULT NULL,
  `is_sub` int(11) DEFAULT 0,
  `saved` int(11) DEFAULT 0,
  `total_hotel_doller` decimal(10,2) DEFAULT NULL,
  `pay_doller_count` decimal(10,2) DEFAULT NULL,
  `verify` int(11) DEFAULT 0,
  `payment_complete` int(11) NOT NULL,
  `payment_complete_user` varchar(150) DEFAULT NULL,
  `payment_complete_date` date DEFAULT NULL,
  `payment_request` int(11) NOT NULL DEFAULT 0,
  `payment_request_user` varchar(100) DEFAULT NULL,
  `payment_request_date` date NOT NULL DEFAULT '2000-01-01',
  `sglfoc` float DEFAULT NULL,
  `dblfoc` float DEFAULT NULL,
  `tplfoc` float DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tour_quotations` DISABLE KEYS */;
/*!40000 ALTER TABLE `tour_quotations` ENABLE KEYS */;
DROP TABLE IF EXISTS `tour_quotations_copy1`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tour_quotations_copy1` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quotation_id` varchar(255) DEFAULT NULL,
  `quotation_day` varchar(255) DEFAULT NULL,
  `hotel_code` varchar(255) DEFAULT NULL,
  `meal_code` varchar(255) DEFAULT NULL,
  `hotel_acc` varchar(255) DEFAULT NULL,
  `hotel_name` varchar(255) DEFAULT NULL,
  `room_type` varchar(255) DEFAULT NULL,
  `room_type_id` int(11) DEFAULT NULL,
  `tour_date` date DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `sgl` decimal(10,2) DEFAULT NULL,
  `dbl` decimal(10,2) DEFAULT NULL,
  `tpl` decimal(10,2) DEFAULT NULL,
  `created_time` datetime DEFAULT NULL,
  `created_user` varchar(255) DEFAULT NULL,
  `distance_type` int(11) DEFAULT NULL,
  `det_flag` int(11) DEFAULT 0,
  `transfer_code` varchar(255) DEFAULT NULL,
  `transfer` varchar(255) DEFAULT NULL,
  `distance` double DEFAULT NULL,
  `from_location` varchar(255) DEFAULT NULL,
  `to_location` varchar(255) DEFAULT NULL,
  `gdistance` double DEFAULT NULL,
  `added_by` varchar(255) DEFAULT NULL,
  `added_time` datetime DEFAULT NULL,
  `trans_flag` int(11) DEFAULT 0,
  `confirm` int(11) DEFAULT 0,
  `finalize` int(11) DEFAULT 0,
  `hotel_advance` decimal(10,2) DEFAULT 0.00,
  `advance_ref` varchar(255) DEFAULT NULL,
  `extra_kilo` double DEFAULT NULL,
  `extra_act_fee` decimal(10,2) DEFAULT 0.00,
  `sglcount` int(11) DEFAULT 0,
  `dblcount` int(11) DEFAULT 0,
  `tplcount` int(11) DEFAULT NULL,
  `hotel_booking_flag` int(11) DEFAULT 0,
  `booking_confirmed_flag` int(11) DEFAULT 0,
  `hotel_payment_flag` int(11) DEFAULT 0,
  `extra_hotel_fee` decimal(10,2) DEFAULT 0.00,
  `hotel_dollar_rate` decimal(10,2) DEFAULT 0.00,
  `confirmation_email` varchar(255) DEFAULT NULL,
  `confirmation_reference` varchar(255) DEFAULT NULL,
  `is_sub` int(11) DEFAULT 0,
  `saved` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tour_quotations_copy1` DISABLE KEYS */;
/*!40000 ALTER TABLE `tour_quotations_copy1` ENABLE KEYS */;
DROP TABLE IF EXISTS `tour_shop_allocation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tour_shop_allocation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tour_id` int(11) DEFAULT NULL,
  `tour_date` date DEFAULT NULL,
  `shop_id` varchar(255) DEFAULT NULL,
  `shop_name` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `allocated_by` varchar(255) DEFAULT NULL,
  `manual_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tour_shop_allocation` DISABLE KEYS */;
/*!40000 ALTER TABLE `tour_shop_allocation` ENABLE KEYS */;
DROP TABLE IF EXISTS `tour_sub_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tour_sub_locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quotation_id` int(11) DEFAULT NULL,
  `date_id` date DEFAULT NULL,
  `from` varchar(255) DEFAULT NULL,
  `to` varchar(255) DEFAULT NULL,
  `km` decimal(10,2) DEFAULT NULL,
  `tour_row` int(11) DEFAULT NULL,
  `estimate_km` decimal(10,2) DEFAULT NULL,
  `extra_km` decimal(10,2) DEFAULT NULL,
  `ref_no` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tour_sub_locations` DISABLE KEYS */;
/*!40000 ALTER TABLE `tour_sub_locations` ENABLE KEYS */;
DROP TABLE IF EXISTS `transfer_milage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `transfer_milage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transfer_code` varchar(255) NOT NULL,
  `transfer` varchar(255) DEFAULT NULL,
  `from` varchar(255) DEFAULT NULL,
  `to` varchar(255) DEFAULT NULL,
  `estimated_mileage` decimal(10,2) DEFAULT NULL,
  `actual_mileage` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `transfer_milage` DISABLE KEYS */;
/*!40000 ALTER TABLE `transfer_milage` ENABLE KEYS */;
DROP TABLE IF EXISTS `usage_note`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usage_note` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usage_note_no` varchar(50) NOT NULL,
  `date` date NOT NULL,
  `tour_id` varchar(50) DEFAULT NULL,
  `activity_id` int(11) DEFAULT NULL,
  `activity_ref_no` varchar(50) DEFAULT NULL,
  `item_code` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, approved, rejected',
  `approved_by` varchar(50) DEFAULT NULL COMMENT 'User acc_no who approved/rejected',
  `approved_at` datetime DEFAULT NULL COMMENT 'Timestamp of approval/rejection',
  `rejection_reason` text DEFAULT NULL COMMENT 'Reason if rejected',
  `remarks` text DEFAULT NULL COMMENT 'General remarks',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_usage_note_no` (`usage_note_no`) USING BTREE,
  KEY `idx_tour_id` (`tour_id`) USING BTREE,
  KEY `idx_activity_id` (`activity_id`) USING BTREE,
  KEY `idx_status` (`status`),
  KEY `idx_usage_note_no_status` (`usage_note_no`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `usage_note` DISABLE KEYS */;
/*!40000 ALTER TABLE `usage_note` ENABLE KEYS */;
DROP TABLE IF EXISTS `user_acc_tbl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_acc_tbl` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_id` int(11) NOT NULL,
  `user` varchar(100) DEFAULT NULL,
  `pass` varchar(255) DEFAULT '',
  `acc_no` varchar(50) DEFAULT NULL,
  `group_id` int(11) NOT NULL,
  `access` int(11) NOT NULL,
  `login_type` int(11) DEFAULT NULL,
  `last_logged` datetime DEFAULT NULL,
  `agent` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `host` varchar(255) DEFAULT NULL,
  `email_password` varchar(255) DEFAULT NULL,
  `user_role` int(11) DEFAULT 0,
  `login_attempts` tinyint(4) NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `user_acc_tbl` DISABLE KEYS */;
INSERT INTO `user_acc_tbl` VALUES (1,9001,'mailtestadmin','','4-8-9-99001',1,0,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL);
/*!40000 ALTER TABLE `user_acc_tbl` ENABLE KEYS */;
DROP TABLE IF EXISTS `user_acc_tbl_copy1`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_acc_tbl_copy1` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_id` int(11) NOT NULL,
  `user` varchar(100) DEFAULT NULL,
  `pass` varchar(255) DEFAULT '',
  `acc_no` varchar(255) DEFAULT NULL,
  `group_id` int(11) NOT NULL,
  `access` int(11) NOT NULL,
  `login_type` int(11) DEFAULT NULL,
  `last_logged` datetime DEFAULT NULL,
  `agent` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `user_acc_tbl_copy1` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_acc_tbl_copy1` ENABLE KEYS */;
DROP TABLE IF EXISTS `user_priv_q`;
/*!50001 DROP VIEW IF EXISTS `user_priv_q`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `user_priv_q` AS SELECT
 1 AS `emp_id`,
  1 AS `name`,
  1 AS `user_id`,
  1 AS `user`,
  1 AS `group_id`,
  1 AS `type`,
  1 AS `cat_id`,
  1 AS `category`,
  1 AS `cat_oid`,
  1 AS `menu_id`,
  1 AS `menu`,
  1 AS `url`,
  1 AS `menu_oid`,
  1 AS `account_no`,
  1 AS `show_in_menu_bar` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `user_privileges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_privileges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `user_privileges` DISABLE KEYS */;
INSERT INTO `user_privileges` VALUES (1,1,0,222);
INSERT INTO `user_privileges` VALUES (2,1,0,225);
INSERT INTO `user_privileges` VALUES (3,1,0,227);
INSERT INTO `user_privileges` VALUES (4,1,0,231);
INSERT INTO `user_privileges` VALUES (5,1,0,243);
/*!40000 ALTER TABLE `user_privileges` ENABLE KEYS */;
DROP TABLE IF EXISTS `utility_bill`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `utility_bill` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_type` varchar(30) NOT NULL DEFAULT 'utility_bill',
  `acc_liability` varchar(50) DEFAULT NULL,
  `acc_name_liability` varchar(255) DEFAULT NULL,
  `acc_expenses` varchar(50) DEFAULT NULL,
  `acc_name_expenses` varchar(255) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `inv_no` varchar(100) DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `units` decimal(10,2) DEFAULT 0.00,
  `rate` decimal(10,4) DEFAULT 0.0000,
  `amount` decimal(15,2) DEFAULT 0.00,
  `cashier` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `accepted_by` varchar(100) DEFAULT NULL,
  `accepted_at` datetime DEFAULT NULL,
  `rejected_by` varchar(100) DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `pay_status` tinyint(1) DEFAULT NULL,
  `pay_status_by` varchar(100) DEFAULT NULL,
  `pay_status_at` datetime DEFAULT NULL,
  `voucher_token` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `utility_bill` DISABLE KEYS */;
/*!40000 ALTER TABLE `utility_bill` ENABLE KEYS */;
DROP TABLE IF EXISTS `v_inv`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `v_inv` (
  `Date` datetime DEFAULT NULL,
  `Invoice` varchar(150) DEFAULT NULL,
  `Description` varchar(250) DEFAULT NULL,
  `Units` double DEFAULT NULL,
  `Rate` decimal(12,2) DEFAULT NULL,
  `Amount` decimal(12,2) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `V_tkn` bigint(20) DEFAULT NULL,
  `H_ID` bigint(20) DEFAULT NULL,
  `acc` varchar(50) DEFAULT NULL,
  `acc_amnt` decimal(12,2) DEFAULT NULL,
  `account` varchar(250) DEFAULT NULL,
  `ityp` int(11) DEFAULT NULL,
  `ref` varchar(150) DEFAULT NULL,
  `GC_Acc` varchar(50) DEFAULT NULL,
  `InvAmount` decimal(12,2) DEFAULT NULL,
  `Due` decimal(12,2) DEFAULT NULL,
  `employee` varchar(250) DEFAULT NULL,
  `link_code` varchar(50) DEFAULT NULL,
  `emp_V_Acc` varchar(150) DEFAULT NULL,
  `emp_V_Acc_no` varchar(50) DEFAULT NULL,
  `with_out_vat` decimal(12,2) DEFAULT NULL,
  `VAT` decimal(12,2) DEFAULT NULL,
  `login` varchar(50) DEFAULT NULL,
  `RCType` int(11) DEFAULT NULL,
  `INV_Date` date NOT NULL,
  `Payee` varchar(100) NOT NULL,
  `acc_run_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `v_inv` DISABLE KEYS */;
/*!40000 ALTER TABLE `v_inv` ENABLE KEYS */;
DROP TABLE IF EXISTS `v_inv_copy1`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `v_inv_copy1` (
  `Date` datetime DEFAULT NULL,
  `Invoice` varchar(150) DEFAULT NULL,
  `Description` varchar(250) DEFAULT NULL,
  `Units` double DEFAULT NULL,
  `Rate` decimal(12,2) DEFAULT NULL,
  `Amount` decimal(12,2) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `V_tkn` bigint(20) DEFAULT NULL,
  `H_ID` bigint(20) DEFAULT NULL,
  `acc` varchar(50) DEFAULT NULL,
  `acc_amnt` decimal(12,2) DEFAULT NULL,
  `account` varchar(250) DEFAULT NULL,
  `ityp` int(11) DEFAULT NULL,
  `ref` varchar(150) DEFAULT NULL,
  `GC_Acc` varchar(50) DEFAULT NULL,
  `InvAmount` decimal(12,2) DEFAULT NULL,
  `Due` decimal(12,2) DEFAULT NULL,
  `employee` varchar(250) DEFAULT NULL,
  `link_code` varchar(50) DEFAULT NULL,
  `emp_V_Acc` varchar(150) DEFAULT NULL,
  `emp_V_Acc_no` varchar(50) DEFAULT NULL,
  `with_out_vat` decimal(12,2) DEFAULT NULL,
  `VAT` decimal(12,2) DEFAULT NULL,
  `login` varchar(50) DEFAULT NULL,
  `RCType` int(11) DEFAULT NULL,
  `INV_Date` date NOT NULL,
  `Payee` varchar(100) NOT NULL,
  `acc_run_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `v_inv_copy1` DISABLE KEYS */;
/*!40000 ALTER TABLE `v_inv_copy1` ENABLE KEYS */;
DROP TABLE IF EXISTS `vehicle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicle` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(255) DEFAULT NULL,
  `passenger_count` int(11) DEFAULT NULL,
  `km_rate` double DEFAULT NULL,
  `v_num` varchar(255) DEFAULT NULL,
  `owner` varchar(255) DEFAULT NULL,
  `ocontact` varchar(255) DEFAULT NULL,
  `bata` decimal(10,2) DEFAULT NULL,
  `action` int(11) DEFAULT 0,
  `vimg` varchar(255) DEFAULT NULL,
  `vlimg` varchar(255) DEFAULT NULL,
  `vehi_license_expire_date` date DEFAULT NULL,
  `viimg` varchar(255) DEFAULT NULL,
  `vehi_insurance_expire_date` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `vehicle` DISABLE KEYS */;
/*!40000 ALTER TABLE `vehicle` ENABLE KEYS */;
DROP TABLE IF EXISTS `visafeedetails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `visafeedetails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `invoice_no` varchar(50) DEFAULT NULL,
  `date` date NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` datetime NOT NULL,
  `created_by` varchar(100) NOT NULL,
  `reference_inv` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `visafeedetails` DISABLE KEYS */;
/*!40000 ALTER TABLE `visafeedetails` ENABLE KEYS */;
DROP TABLE IF EXISTS `voucher`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `voucher` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Acc_No` varchar(25) NOT NULL,
  `Pay` varchar(150) NOT NULL,
  `C_Date` datetime NOT NULL,
  `W_Date` datetime NOT NULL,
  `C_Number` varchar(20) NOT NULL,
  `Amount` decimal(12,2) NOT NULL,
  `print` varchar(200) NOT NULL,
  `des` varchar(100) NOT NULL,
  `Address` varchar(200) NOT NULL,
  `Bank` varchar(50) NOT NULL,
  `Vnum` int(11) NOT NULL,
  `S_name` varchar(100) NOT NULL,
  `inv` varchar(200) NOT NULL,
  `flag` int(11) NOT NULL,
  `Bank_acc` varchar(50) NOT NULL,
  `dv` int(11) NOT NULL,
  `vcat` varchar(50) DEFAULT NULL,
  `login` varchar(50) DEFAULT NULL,
  `bookt` int(11) DEFAULT NULL,
  `Aflag` int(11) DEFAULT NULL,
  `app_user` varchar(50) DEFAULT NULL,
  `Aut_user` varchar(50) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `rej_user` varchar(50) DEFAULT NULL,
  `printflg` int(11) DEFAULT NULL,
  `sup` int(11) DEFAULT NULL,
  `VAT` int(11) DEFAULT NULL,
  `VAT_Amt` decimal(12,2) DEFAULT NULL,
  `vat_m` int(11) DEFAULT NULL,
  `nbt` int(11) DEFAULT NULL,
  `nbt_amt` decimal(12,2) DEFAULT NULL,
  `nbt_m` int(11) DEFAULT NULL,
  `g_ttl` decimal(12,2) DEFAULT NULL,
  `v_typ` varchar(50) DEFAULT NULL,
  `ref_po` varchar(150) DEFAULT NULL,
  `stmp_d` int(11) DEFAULT NULL,
  `V_num` varchar(250) DEFAULT NULL,
  `ref_account` varchar(250) DEFAULT NULL,
  `ref_acc_no` varchar(50) DEFAULT NULL,
  `wht_active` int(11) DEFAULT NULL,
  `wht_p` double DEFAULT NULL,
  `wht_amount` decimal(12,2) DEFAULT NULL,
  `aut_date` datetime DEFAULT NULL,
  `Recommendation` bigint(20) DEFAULT NULL,
  `emp_code` varchar(50) DEFAULT NULL,
  `rej` varchar(50) DEFAULT NULL,
  `rejlogin` varchar(50) DEFAULT NULL,
  `rejdate` datetime DEFAULT NULL,
  `aut_login` varchar(50) DEFAULT NULL,
  `print_login` varchar(50) DEFAULT NULL,
  `vnumber` varchar(50) DEFAULT NULL,
  `insflag` int(11) NOT NULL,
  `vcher` varchar(150) NOT NULL,
  `unrlz_acc` varchar(50) DEFAULT NULL,
  `Voucher_No` int(11) DEFAULT NULL,
  `acc_run_id` int(11) DEFAULT NULL,
  `is_expenses` int(11) DEFAULT 0,
  `advances_pay` int(11) DEFAULT 0,
  `doller_amt` decimal(12,2) DEFAULT NULL,
  `is_confirm` int(11) NOT NULL DEFAULT 0,
  `confirm_user` varchar(150) DEFAULT '0',
  `confirm_date` datetime DEFAULT NULL,
  `is_special` int(11) DEFAULT 0,
  `finalized` int(11) DEFAULT 0,
  `finalized_user` varchar(255) DEFAULT NULL,
  `finalized_date` datetime DEFAULT NULL,
  `bank_date` date DEFAULT NULL,
  `remark` varchar(500) DEFAULT NULL COMMENT 'Remark entered on approve/finalize (optional) or cancel/reject (required)',
  PRIMARY KEY (`ID`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `voucher` DISABLE KEYS */;
/*!40000 ALTER TABLE `voucher` ENABLE KEYS */;
/*!50001 DROP VIEW IF EXISTS `acc_selct`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */

/*!50001 VIEW `acc_selct` AS select `account_basement`.`B_Code` AS `B_Code`,`account_basement`.`BName` AS `BName`,`account_basement`.`balflag` AS `balflag`,`account_titel`.`T_Code` AS `T_Code`,`account_titel`.`TName` AS `TName`,`account_heading`.`HName` AS `HName`,`account_heading`.`H_Code` AS `H_Code`,`account_posting`.`P_Code` AS `P_Code`,`account_posting`.`PName` AS `PName`,`account_posting`.`L_Code` AS `L_Code` from (`account_posting` join (`account_heading` join (`account_titel` join `account_basement` on(`account_titel`.`B_Code` = `account_basement`.`B_Code`)) on(`account_heading`.`T_Code` = `account_titel`.`T_Code` and `account_heading`.`B_Code` = `account_basement`.`B_Code`)) on(`account_posting`.`B_Code` = `account_basement`.`B_Code` and `account_posting`.`T_Code` = `account_titel`.`T_Code` and `account_posting`.`H_Code` = `account_heading`.`H_Code`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `account_runing_q2`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */

/*!50001 VIEW `account_runing_q2` AS select `account_runing_qq`.`index_no` AS `TourId`,`account_runing_qq`.`BName` AS `BName`,`account_runing_qq`.`TName` AS `TName`,`account_runing_qq`.`HName` AS `HName`,`account_runing_qq`.`PName` AS `PName`,`account_runing_qq`.`L_Code` AS `L_Code`,`account_runing_qq`.`Date` AS `Date`,`account_runing_qq`.`Dr` AS `Dr`,`account_runing_qq`.`Cr` AS `Cr`,`account_runing_qq`.`ID` AS `ID`,`account_runing_qq`.`balflag` AS `balflag`,`account_runing_qq`.`Recpt_No` AS `Recpt_No`,case when `account_runing_qq`.`balflag` = 1 then `account_runing_qq`.`Dr` - `account_runing_qq`.`Cr` else `account_runing_qq`.`Cr` - `account_runing_qq`.`Dr` end AS `bal`,`account_runing_qq`.`Discription` AS `Discription` from `account_runing_qq` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `account_runing_q21`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */

/*!50001 VIEW `account_runing_q21` AS select `account_runing_qq`.`BName` AS `BName`,`account_runing_qq`.`TName` AS `TName`,`account_runing_qq`.`HName` AS `HName`,`account_runing_qq`.`PName` AS `PName`,`account_runing_qq`.`Date` AS `Date`,`account_runing_qq`.`Dr` AS `Dr`,`account_runing_qq`.`Cr` AS `Cr`,case when `account_runing_qq`.`balflag` = 1 then `account_runing_qq`.`Dr` - `account_runing_qq`.`Cr` else `account_runing_qq`.`Cr` - `account_runing_qq`.`Dr` end AS `bal` from `account_runing_qq` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `account_runing_qq`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */

/*!50001 VIEW `account_runing_qq` AS select `account_basement`.`BName` AS `BName`,`account_titel`.`TName` AS `TName`,`account_heading`.`HName` AS `HName`,`account_posting`.`PName` AS `PName`,`account_posting`.`L_Code` AS `L_Code`,`account_runing`.`ID` AS `ID`,`account_runing`.`Dr` AS `Dr`,`account_runing`.`Cr` AS `Cr`,`account_runing`.`Discription` AS `Discription`,`account_runing`.`Date` AS `Date`,`account_runing`.`Cashier` AS `Cashier`,`account_runing`.`status` AS `status`,`account_runing`.`Chk_No` AS `Chk_No`,`account_runing`.`DV` AS `DV`,`account_runing`.`Invoice_Num` AS `Invoice_Num`,`account_runing`.`Linkacc` AS `Linkacc`,`account_runing`.`aflag` AS `aflag`,`account_runing`.`job` AS `job`,`account_runing`.`index_no` AS `index_no`,`account_runing`.`P_Cap` AS `P_Cap`,`account_runing`.`New_L_Code` AS `New_L_Code`,`account_runing`.`P_Month` AS `P_Month`,`account_runing`.`Recpt_No` AS `Recpt_No`,`account_runing`.`Bank_Name` AS `Bank_Name`,`account_runing`.`Chk_Date` AS `Chk_Date`,`account_runing`.`Bank_Inv` AS `Bank_Inv`,`account_runing`.`Bank_Date` AS `Bank_Date`,`account_runing`.`Card_No` AS `Card_No`,`account_runing`.`shift_id` AS `shift_id`,`account_basement`.`balflag` AS `balflag`,`account_runing`.`Sup_Invoice` AS `Sup_Invoice`,`account_runing`.`bankst_flag` AS `bankst_flag`,`account_runing`.`bank_rec` AS `bank_rec`,`account_runing`.`dollarrate` AS `dollarrate`,`account_runing`.`dollar` AS `dollar`,`account_runing`.`dollar_cr` AS `dollar_cr`,`account_runing`.`dollarrate_cr` AS `dollarrate_cr` from ((((`account_basement` join `account_titel` on(`account_titel`.`B_Code` = `account_basement`.`B_Code`)) join `account_heading` on(`account_heading`.`B_Code` = `account_basement`.`B_Code` and `account_heading`.`T_Code` = `account_titel`.`T_Code`)) join `account_posting` on(`account_posting`.`B_Code` = `account_basement`.`B_Code` and `account_posting`.`T_Code` = `account_titel`.`T_Code` and `account_posting`.`H_Code` = `account_heading`.`H_Code`)) join `account_runing` on(`account_posting`.`L_Code` = `account_runing`.`L_Code`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `bank_statement`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */

/*!50001 VIEW `bank_statement` AS select `account_runing_qq`.`BName` AS `BName`,`account_runing_qq`.`TName` AS `TName`,`account_runing_qq`.`HName` AS `HName`,`account_runing_qq`.`PName` AS `PName`,`account_runing_qq`.`Invoice_Num` AS `Invoice_Num`,`account_runing_qq`.`New_L_Code` AS `New_L_Code`,`account_runing_qq`.`L_Code` AS `L_Code`,`account_runing_qq`.`Dr` AS `Dr`,`account_runing_qq`.`Cr` AS `Cr`,`account_runing_qq`.`Cashier` AS `Cashier`,`account_runing_qq`.`ID` AS `ID`,`account_runing_qq`.`Discription` AS `Discription`,`account_runing_qq`.`Date` AS `Date`,`account_runing_qq`.`status` AS `status`,`account_runing_qq`.`Chk_No` AS `Chk_No`,`account_runing_qq`.`Linkacc` AS `Linkacc`,`account_runing_qq`.`aflag` AS `aflag`,`account_runing_qq`.`job` AS `job`,`account_runing_qq`.`Sup_Invoice` AS `Sup_Invoice`,`account_runing_qq`.`bankst_flag` AS `bankst_flag`,`account_runing_qq`.`bank_rec` AS `bank_rec` from `account_runing_qq` where `account_runing_qq`.`TName` = 'Other Assets' */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `chk_filter`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */

/*!50001 VIEW `chk_filter` AS select `account_runing_qq`.`PName` AS `PName`,sum(`account_runing_qq`.`Dr`) AS `SDr`,sum(`account_runing_qq`.`Cr`) AS `SCr`,`account_runing_qq`.`Chk_No` AS `Chk_No`,sum(`account_runing_qq`.`Dr` - `account_runing_qq`.`Cr`) AS `Bal`,`account_runing_qq`.`L_Code` AS `L_Code`,`account_runing_qq`.`DV` AS `DV`,`account_runing_qq`.`index_no` AS `index_no` from `account_runing_qq` group by `account_runing_qq`.`PName`,`account_runing_qq`.`Chk_No`,`account_runing_qq`.`L_Code`,`account_runing_qq`.`DV`,`account_runing_qq`.`index_no` having `account_runing_qq`.`PName` = 'Cheques In Hand' */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `debtors_qq2`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */

/*!50001 VIEW `debtors_qq2` AS select `arq`.`HName` AS `HName`,sum(`arq`.`Dr`) AS `SDr`,sum(`arq`.`Cr`) AS `SCr`,`arq`.`Invoice_Num` AS `Invoice_Num`,`arq`.`status` AS `status`,sum(`arq`.`Dr`) - sum(`arq`.`Cr`) AS `Bal`,`arq`.`L_Code` AS `L_Code`,min(`arq`.`Date`) AS `FirstOfDate`,`arq`.`PName` AS `PName`,`arq`.`index_no` AS `index_no`,sum(case when to_days(curdate()) - to_days(`arq`.`Date`) <= 30 then `arq`.`Dr` - `arq`.`Cr` else 0 end) AS `Balance_30_Days`,sum(case when to_days(curdate()) - to_days(`arq`.`Date`) > 30 and to_days(curdate()) - to_days(`arq`.`Date`) <= 60 then `arq`.`Dr` - `arq`.`Cr` else 0 end) AS `Balance_60_Days`,sum(case when to_days(curdate()) - to_days(`arq`.`Date`) > 60 and to_days(curdate()) - to_days(`arq`.`Date`) <= 90 then `arq`.`Dr` - `arq`.`Cr` else 0 end) AS `Balance_90_Days`,sum(case when to_days(curdate()) - to_days(`arq`.`Date`) > 90 then `arq`.`Dr` - `arq`.`Cr` else 0 end) AS `Balance_Over_90_Days` from `account_runing_qq` `arq` group by `arq`.`HName`,`arq`.`Invoice_Num`,`arq`.`status`,`arq`.`L_Code`,`arq`.`PName` having `arq`.`HName` = 'Trade Debtors' */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `deptors_qq`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */

/*!50001 VIEW `deptors_qq` AS select `account_runing_qq`.`HName` AS `HName`,sum(`account_runing_qq`.`Dr`) AS `SDr`,sum(`account_runing_qq`.`Cr`) AS `SCr`,`account_runing_qq`.`Invoice_Num` AS `Invoice_Num`,`account_runing_qq`.`status` AS `status`,sum(`account_runing_qq`.`Dr`) - sum(`account_runing_qq`.`Cr`) AS `Bal`,`account_runing_qq`.`L_Code` AS `L_Code`,min(`account_runing_qq`.`Date`) AS `FirstOfDate`,`account_runing_qq`.`PName` AS `PName`,`account_runing_qq`.`index_no` AS `index_no`,`account_runing_qq`.`dollarrate` AS `dollarrate`,`account_runing_qq`.`dollar` AS `dollar` from `account_runing_qq` group by `account_runing_qq`.`HName`,`account_runing_qq`.`Invoice_Num`,`account_runing_qq`.`status`,`account_runing_qq`.`L_Code`,`account_runing_qq`.`PName` having `account_runing_qq`.`HName` = 'Trade Debtors' */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `deptors_qq_dollar`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */

/*!50001 VIEW `deptors_qq_dollar` AS select `account_runing_qq`.`HName` AS `HName`,sum(`account_runing_qq`.`Dr`) AS `SDr`,sum(`account_runing_qq`.`Cr`) AS `SCr`,`account_runing_qq`.`Invoice_Num` AS `Invoice_Num`,`account_runing_qq`.`status` AS `status`,sum(`account_runing_qq`.`Dr`) - sum(`account_runing_qq`.`Cr`) AS `Bal`,sum(`account_runing_qq`.`dollar`) AS `DDr`,sum(`account_runing_qq`.`dollar_cr`) AS `DCr`,sum(`account_runing_qq`.`dollar`) - sum(`account_runing_qq`.`dollar_cr`) AS `DBal`,`account_runing_qq`.`L_Code` AS `L_Code`,min(`account_runing_qq`.`Date`) AS `FirstOfDate`,`account_runing_qq`.`PName` AS `PName`,`account_runing_qq`.`index_no` AS `index_no`,`account_runing_qq`.`dollarrate` AS `dollarrate`,`account_runing_qq`.`dollar` AS `dollar`,`account_runing_qq`.`Discription` AS `Discription`,`account_runing_qq`.`dollarrate_cr` AS `dollarrate_cr`,`account_runing_qq`.`dollar_cr` AS `dollar_cr` from `account_runing_qq` group by `account_runing_qq`.`HName`,`account_runing_qq`.`Invoice_Num`,`account_runing_qq`.`status`,`account_runing_qq`.`L_Code`,`account_runing_qq`.`PName` having `account_runing_qq`.`HName` = 'Trade Debtors' */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `inventory_stock_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */

/*!50001 VIEW `inventory_stock_view` AS select `sc`.`Stock_Card` AS `Stock_Card`,`sc`.`Descrip` AS `item_description`,`sc`.`reorder` AS `reorder_level`,`sc`.`pp` AS `purchase_price`,coalesce(sum(`s`.`Dr_Qty`),0) - coalesce(sum(`s`.`Cr_Qty`),0) AS `current_balance`,coalesce(`cost`.`Avg_Cost`,0) AS `avg_cost`,coalesce(`cost`.`Last_Cost`,0) AS `last_cost`,case when coalesce(sum(`s`.`Dr_Qty`),0) - coalesce(sum(`s`.`Cr_Qty`),0) <= 0 then 'Out of Stock' when coalesce(sum(`s`.`Dr_Qty`),0) - coalesce(sum(`s`.`Cr_Qty`),0) <= `sc`.`reorder` then 'Low Stock' else 'In Stock' end AS `stock_status` from ((`stock_card` `sc` left join `stock` `s` on(`sc`.`Stock_Card` = `s`.`Stock_Card`)) left join `stock_cost` `cost` on(`sc`.`Stock_Card` = `cost`.`Stock_Card`)) group by `sc`.`Stock_Card`,`sc`.`Descrip`,`sc`.`reorder`,`sc`.`pp`,`cost`.`Avg_Cost`,`cost`.`Last_Cost` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `supplier_qq`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */

/*!50001 VIEW `supplier_qq` AS select `account_runing_qq`.`HName` AS `HName`,sum(`account_runing_qq`.`Dr`) AS `SDr`,sum(`account_runing_qq`.`Cr`) AS `SCr`,`account_runing_qq`.`Invoice_Num` AS `Invoice_Num`,`account_runing_qq`.`status` AS `status`,sum(`account_runing_qq`.`Cr`) - sum(`account_runing_qq`.`Dr`) AS `Bal`,`account_runing_qq`.`L_Code` AS `L_Code`,min(`account_runing_qq`.`Date`) AS `FirstOfDate`,`account_runing_qq`.`PName` AS `PName`,`account_runing_qq`.`index_no` AS `index_no` from `account_runing_qq` group by `account_runing_qq`.`HName`,`account_runing_qq`.`Invoice_Num`,`account_runing_qq`.`status`,`account_runing_qq`.`L_Code`,`account_runing_qq`.`PName` having `account_runing_qq`.`HName` = 'Supplier Accounts' */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `supplier_qq2`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */

/*!50001 VIEW `supplier_qq2` AS select `arq`.`HName` AS `HName`,sum(`arq`.`Dr`) AS `SDr`,sum(`arq`.`Cr`) AS `SCr`,`arq`.`Invoice_Num` AS `Invoice_Num`,`arq`.`status` AS `status`,sum(`arq`.`Cr`) - sum(`arq`.`Dr`) AS `Bal`,`arq`.`L_Code` AS `L_Code`,min(`arq`.`Date`) AS `FirstOfDate`,`arq`.`PName` AS `PName`,`arq`.`index_no` AS `index_no`,sum(case when to_days(curdate()) - to_days(`arq`.`Date`) <= 30 then `arq`.`Cr` - `arq`.`Dr` else 0 end) AS `Balance_30_Days`,sum(case when to_days(curdate()) - to_days(`arq`.`Date`) > 30 and to_days(curdate()) - to_days(`arq`.`Date`) <= 60 then `arq`.`Cr` - `arq`.`Dr` else 0 end) AS `Balance_60_Days`,sum(case when to_days(curdate()) - to_days(`arq`.`Date`) > 60 and to_days(curdate()) - to_days(`arq`.`Date`) <= 90 then `arq`.`Cr` - `arq`.`Dr` else 0 end) AS `Balance_90_Days`,sum(case when to_days(curdate()) - to_days(`arq`.`Date`) > 90 then `arq`.`Cr` - `arq`.`Dr` else 0 end) AS `Balance_Over_90_Days` from `account_runing_qq` `arq` group by `arq`.`HName`,`arq`.`Invoice_Num`,`arq`.`status`,`arq`.`L_Code`,`arq`.`PName` having `arq`.`HName` = 'Supplier Accounts' */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `user_priv_q`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */

/*!50001 VIEW `user_priv_q` AS select `user_acc_tbl`.`emp_id` AS `emp_id`,`employee`.`Name` AS `name`,`user_acc_tbl`.`id` AS `user_id`,`user_acc_tbl`.`user` AS `user`,`user_acc_tbl`.`group_id` AS `group_id`,`group_profile`.`type` AS `type`,`menu_list`.`cat_id` AS `cat_id`,`menu_category`.`category` AS `category`,`menu_category`.`order_id` AS `cat_oid`,`user_privileges`.`menu_id` AS `menu_id`,`menu_list`.`name` AS `menu`,`menu_list`.`url` AS `url`,`menu_list`.`order_id` AS `menu_oid`,`employee`.`Acc_No` AS `account_no`,`menu_list`.`show_in_menu_bar` AS `show_in_menu_bar` from (((((`user_acc_tbl` join `user_privileges` on(`user_acc_tbl`.`group_id` = `user_privileges`.`group_id`)) join `employee` on(`employee`.`emp_num` = `user_acc_tbl`.`emp_id`)) join `menu_list` on(`user_privileges`.`menu_id` = `menu_list`.`id`)) join `menu_category` on(`menu_category`.`id` = `menu_list`.`cat_id`)) join `group_profile` on(`group_profile`.`id` = `user_acc_tbl`.`group_id`)) where `user_acc_tbl`.`access` = '1' and `menu_list`.`del` = '0' order by `user_acc_tbl`.`emp_id`,`menu_category`.`order_id`,`menu_list`.`order_id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

/*M!999999\- enable the sandbox mode */ 

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `ci_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ci_sessions` (
  `id` varchar(128) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `data` blob NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ci_sessions_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `token` varchar(128) NOT NULL,
  `session_id` varchar(128) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `revoke_reason` varchar(50) DEFAULT NULL,
  `revoked_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_activity` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IX_user_sessions_uid` (`uid`),
  KEY `IX_user_sessions_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `a_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `a_log` (
  `Usr` varchar(50) DEFAULT NULL,
  `Panel` varchar(100) DEFAULT NULL,
  `Url` varchar(100) DEFAULT NULL,
  `Date` datetime DEFAULT NULL,
  `Ordr` int(11) DEFAULT NULL,
  `Tym` time DEFAULT NULL,
  `ID` bigint(20) NOT NULL AUTO_INCREMENT,
  `p_id` int(11) DEFAULT NULL,
  `h_id` int(11) DEFAULT NULL,
  `up` int(11) DEFAULT NULL,
  `ip` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ID`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

UPDATE user_acc_tbl SET pass = '', access = 0 WHERE user = 'mailtestadmin';
