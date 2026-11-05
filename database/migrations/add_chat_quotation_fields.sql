-- Database Schema Updates for Chat-Quotation Integration
-- Run these SQL commands to add necessary fields for the chat quotation feature

-- 1. Add fields to consignee table for chat integration
ALTER TABLE consignee
ADD COLUMN chat_conversation_id INT NULL COMMENT 'Links quotation to chat conversation',
ADD COLUMN created_by_agent BOOLEAN DEFAULT 0 COMMENT 'Indicates if quotation was created by external agent',
ADD COLUMN agent_phase_completion ENUM('none','phase1','phase2','phase3') DEFAULT 'none' COMMENT 'Tracks which phases agent has completed';

-- 2. Add fields to conversations table for quotation tracking
ALTER TABLE conversations
ADD COLUMN active_quotation_id INT NULL COMMENT 'Links conversation to active quotation',
ADD COLUMN quotation_status ENUM('none','in_progress','agent_completed','finalized') DEFAULT 'none' COMMENT 'Status of quotation in conversation';

-- 3. Add indexes for performance
CREATE INDEX idx_consignee_chat_conversation ON consignee(chat_conversation_id);
CREATE INDEX idx_consignee_created_by_agent ON consignee(created_by_agent);
CREATE INDEX idx_consignee_agent_phase ON consignee(agent_phase_completion);
CREATE INDEX idx_conversations_quotation ON conversations(active_quotation_id);
CREATE INDEX idx_conversations_quotation_status ON conversations(quotation_status);

-- 4. Add foreign key constraints (optional - remove if tables don't support foreign keys)
-- ALTER TABLE consignee
-- ADD CONSTRAINT fk_consignee_conversation
-- FOREIGN KEY (chat_conversation_id) REFERENCES conversations(id) ON DELETE SET NULL;

-- ALTER TABLE conversations
-- ADD CONSTRAINT fk_conversation_quotation
-- FOREIGN KEY (active_quotation_id) REFERENCES consignee(tour_id) ON DELETE SET NULL;

-- 5. Update existing data (if needed)
-- Set existing quotations as not created by agent
UPDATE consignee SET created_by_agent = 0 WHERE created_by_agent IS NULL;

-- Set existing conversations as having no quotation
UPDATE conversations SET quotation_status = 'none' WHERE quotation_status IS NULL;