-- Campaign buyer/seller messaging (optional manual run; app auto-creates via messaging_service).

CREATE TABLE IF NOT EXISTS campaign_conversations (
  conversation_id INT AUTO_INCREMENT PRIMARY KEY,
  campaign_id INT NOT NULL,
  buyer_id INT NOT NULL,
  seller_id INT NOT NULL,
  participant_id INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_campaign_buyer (campaign_id, buyer_id),
  INDEX (seller_id),
  INDEX (buyer_id),
  CONSTRAINT campaign_conversations_campaign_fk FOREIGN KEY (campaign_id)
    REFERENCES group_buy_campaigns (campaign_id) ON DELETE CASCADE,
  CONSTRAINT campaign_conversations_buyer_fk FOREIGN KEY (buyer_id)
    REFERENCES buyers (buyer_id) ON DELETE CASCADE,
  CONSTRAINT campaign_conversations_seller_fk FOREIGN KEY (seller_id)
    REFERENCES sellers (seller_id) ON DELETE CASCADE,
  CONSTRAINT campaign_conversations_participant_fk FOREIGN KEY (participant_id)
    REFERENCES campaign_participants (participant_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campaign_messages (
  message_id INT AUTO_INCREMENT PRIMARY KEY,
  conversation_id INT NOT NULL,
  sender_id INT NOT NULL,
  body TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (conversation_id),
  INDEX (sender_id),
  CONSTRAINT campaign_messages_conversation_fk FOREIGN KEY (conversation_id)
    REFERENCES campaign_conversations (conversation_id) ON DELETE CASCADE,
  CONSTRAINT campaign_messages_sender_fk FOREIGN KEY (sender_id)
    REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
