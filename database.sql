-- Knowledge Exchange Hub Database Schema
-- Note: Database selection is handled by phpMyAdmin / host panel
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `webrtc_signals`;
DROP TABLE IF EXISTS `video_calls`;
DROP TABLE IF EXISTS `reports`;
DROP TABLE IF EXISTS `certificates`;
DROP TABLE IF EXISTS `points`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `learning_requests`;
DROP TABLE IF EXISTS `user_skills`;
DROP TABLE IF EXISTS `skills`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `profile_photo` VARCHAR(255) DEFAULT 'default_avatar.png',
  `college` VARCHAR(150) DEFAULT NULL,
  `department` VARCHAR(100) DEFAULT NULL,
  `bio` TEXT DEFAULT NULL,
  `points` INT DEFAULT 0,
  `is_admin` TINYINT(1) DEFAULT 0,
  `is_blocked` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_name` VARCHAR(100) NOT NULL UNIQUE,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Skills Table
CREATE TABLE IF NOT EXISTS `skills` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `skill_name` VARCHAR(100) NOT NULL,
  `category_id` INT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. User Skills Table
CREATE TABLE IF NOT EXISTS `user_skills` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `skill_id` INT NOT NULL,
  `skill_type` ENUM('teach', 'learn') NOT NULL,
  `proficiency_level` ENUM('Beginner', 'Intermediate', 'Advanced', 'Expert') DEFAULT 'Intermediate',
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`skill_id`) REFERENCES `skills`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Learning Requests Table
CREATE TABLE IF NOT EXISTS `learning_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `learner_id` INT NOT NULL,
  `mentor_id` INT NOT NULL,
  `skill_id` INT NOT NULL,
  `message` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'accepted', 'rejected', 'completed', 'cancelled') DEFAULT 'pending',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`learner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`mentor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`skill_id`) REFERENCES `skills`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Sessions Table
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `request_id` INT NOT NULL,
  `learner_id` INT NOT NULL,
  `mentor_id` INT NOT NULL,
  `session_date` DATE NOT NULL,
  `session_time` TIME NOT NULL,
  `session_topic` VARCHAR(255) NOT NULL,
  `room_id` VARCHAR(100) NOT NULL,
  `status` ENUM('scheduled', 'ongoing', 'completed', 'cancelled') DEFAULT 'scheduled',
  `learner_confirmed` TINYINT(1) DEFAULT 0,
  `mentor_confirmed` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`request_id`) REFERENCES `learning_requests`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`learner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`mentor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Messages Table
CREATE TABLE IF NOT EXISTS `messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sender_id` INT NOT NULL,
  `receiver_id` INT NOT NULL,
  `request_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`request_id`) REFERENCES `learning_requests`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Reviews Table
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `session_id` INT DEFAULT NULL,
  `reviewer_id` INT NOT NULL,
  `reviewed_user_id` INT NOT NULL,
  `rating` TINYINT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `review_text` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`session_id`) REFERENCES `sessions`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`reviewer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`reviewed_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Points Table
CREATE TABLE IF NOT EXISTS `points` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `points` INT NOT NULL,
  `reason` VARCHAR(255) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Certificates Table
CREATE TABLE IF NOT EXISTS `certificates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `certificate_name` VARCHAR(150) NOT NULL,
  `certificate_file` VARCHAR(255) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Reports Table
CREATE TABLE IF NOT EXISTS `reports` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `reported_by` INT NOT NULL,
  `reported_user` INT NOT NULL,
  `reason` TEXT NOT NULL,
  `status` ENUM('pending', 'reviewed', 'dismissed') DEFAULT 'pending',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`reported_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`reported_user`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. Video Calls Table
CREATE TABLE IF NOT EXISTS `video_calls` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `session_id` INT NOT NULL,
  `room_id` VARCHAR(100) NOT NULL,
  `caller_id` INT NOT NULL,
  `receiver_id` INT NOT NULL,
  `call_status` ENUM('active', 'ended') DEFAULT 'active',
  `started_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `ended_at` DATETIME DEFAULT NULL,
  FOREIGN KEY (`session_id`) REFERENCES `sessions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. WebRTC Signals Table
CREATE TABLE IF NOT EXISTS `webrtc_signals` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `room_id` VARCHAR(100) NOT NULL,
  `sender_id` INT NOT NULL,
  `receiver_id` INT NOT NULL,
  `signal_type` ENUM('offer', 'answer', 'ice-candidate') NOT NULL,
  `signal_data` LONGTEXT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (`room_id`),
  INDEX (`receiver_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- SEED DATA
-- Default Passwords: 
-- Admin: admin@keh.com / admin123  ($2y$10$89b9h/Rlh/mSt0FfT2fSVOgJjL42P7n2dO0V1O89zB0vD7EaLdMee)
-- Test Users: password123 ($2y$10$qVn2FmSgL.t/5o3s2FwO8uM0/kS1rD1Ww0o1v2y3x4z5a6b7c8d9e)
-- ========================================================

-- Seed Categories
INSERT IGNORE INTO `categories` (`id`, `category_name`) VALUES
(1, 'Programming'),
(2, 'Web Development'),
(3, 'Graphic Design'),
(4, 'Video Editing'),
(5, 'UI/UX Design'),
(6, 'Music'),
(7, 'Public Speaking'),
(8, 'Languages'),
(9, 'Sports'),
(10, 'Business'),
(11, 'Others');

-- Seed Skills
INSERT IGNORE INTO `skills` (`id`, `skill_name`, `category_id`) VALUES
(1, 'Java Programming', 1),
(2, 'Python Data Science', 1),
(3, 'C++ Algorithms', 1),
(4, 'ReactJS Frontend', 2),
(5, 'PHP & MySQL Backend', 2),
(6, 'Adobe Photoshop & Illustrator', 3),
(7, 'Premiere Pro & DaVinci', 4),
(8, 'Figma Prototyping', 5),
(9, 'Acoustic Guitar', 6),
(10, 'Debating & Speech', 7),
(11, 'Spanish Conversational', 8),
(12, 'Chess Strategies', 9);

-- Seed Users
-- Password for all seed users is: password123 (hash: $2y$10$wT38Y.HnJ6Z6s/p7d2d0xed9hW24G2hN4q7M2O6gE2v1x.O4G2V7G)
INSERT IGNORE INTO `users` (`id`, `name`, `email`, `password`, `profile_photo`, `college`, `department`, `bio`, `points`, `is_admin`, `is_blocked`) VALUES
(1, 'System Admin', 'admin@keh.com', '$2y$10$eHGxpFJ9Ngc.hh3Ql833oegSxT/kiN6AcG0iPa41rdE1400PDde5O', 'default_avatar.png', 'Tech University', 'Administration', 'Platform Administrator & Coordinator.', 100, 1, 0),
(2, 'Rahul Sharma', 'rahul@student.edu', '$2y$10$eHGxpFJ9Ngc.hh3Ql833oegSxT/kiN6AcG0iPa41rdE1400PDde5O', 'avatar1.png', 'National Institute of Technology', 'Computer Science', 'Passionate Java programmer & competitive coding enthusiast. Excited to learn UI/UX design and Guitar!', 150, 0, 0),
(3, 'Krishna Patel', 'krishna@student.edu', '$2y$10$eHGxpFJ9Ngc.hh3Ql833oegSxT/kiN6AcG0iPa41rdE1400PDde5O', 'avatar2.png', 'College of Engineering & Tech', 'Information Technology', 'Graphic designer with 3 years of freelancing experience. Looking to learn Python for data science.', 125, 0, 0),
(4, 'Aman Verma', 'aman@student.edu', '$2y$10$eHGxpFJ9Ngc.hh3Ql833oegSxT/kiN6AcG0iPa41rdE1400PDde5O', 'avatar3.png', 'State University of Design', 'Media Arts', 'Video editor and motion graphics creator. Love teaching Premiere Pro and learning Web Development.', 100, 0, 0),
(5, 'Sneha Rao', 'sneha@student.edu', '$2y$10$eHGxpFJ9Ngc.hh3Ql833oegSxT/kiN6AcG0iPa41rdE1400PDde5O', 'avatar4.png', 'City Arts College', 'Humanities', 'Fluent Spanish speaker and public speaking coach. Eager to pick up C++ algorithms.', 85, 0, 0),
(6, 'Krushnakant Nile', 'krishnanile129@gmail.com', '$2y$10$eHGxpFJ9Ngc.hh3Ql833oegSxT/kiN6AcG0iPa41rdE1400PDde5O', 'default_avatar.png', 'Zeal College of Engineering', 'Computer Science', 'Full Stack Developer & Peer Tutor.', 120, 0, 0),
(7, 'Swarali Pahane', 'pahaneswarali@gmail.com', '$2y$10$eHGxpFJ9Ngc.hh3Ql833oegSxT/kiN6AcG0iPa41rdE1400PDde5O', 'default_avatar.png', 'Zeal College of Engineering', 'Computer Science', 'Passionate learner & student mentor.', 100, 0, 0),
(8, 'Sakul', 'sakulmahajan40@gmail.com', '$2y$10$eHGxpFJ9Ngc.hh3Ql833oegSxT/kiN6AcG0iPa41rdE1400PDde5O', 'default_avatar.png', 'Zeal College of Engineering', 'Computer Science', 'Software developer & technology enthusiast.', 110, 0, 0),
(9, 'Suhani', 'suhanipendam1403@gmail.com', '$2y$10$eHGxpFJ9Ngc.hh3Ql833oegSxT/kiN6AcG0iPa41rdE1400PDde5O', 'default_avatar.png', 'Zeal College of Engineering', 'Information Technology', 'Creative designer and frontend enthusiast.', 105, 0, 0),
(10, 'Sanskruti', 'sanskrutibhojane06@gmail.com', '$2y$10$eHGxpFJ9Ngc.hh3Ql833oegSxT/kiN6AcG0iPa41rdE1400PDde5O', 'default_avatar.png', 'Zeal College of Engineering', 'Computer Science', 'AI & Machine Learning enthusiast.', 95, 0, 0);

-- Seed User Skills
-- Rahul teaches Java (Expert), wants to learn Figma (Beginner)
INSERT IGNORE INTO `user_skills` (`user_id`, `skill_id`, `skill_type`, `proficiency_level`) VALUES
(2, 1, 'teach', 'Expert'),
(2, 3, 'teach', 'Advanced'),
(2, 8, 'learn', 'Beginner'),
(2, 9, 'learn', 'Beginner'),
-- Krishna teaches Graphic Design (Expert), wants to learn Python (Intermediate)
(3, 6, 'teach', 'Expert'),
(3, 8, 'teach', 'Advanced'),
(3, 2, 'learn', 'Beginner'),
-- Aman teaches Video Editing (Advanced), wants to learn PHP (Intermediate)
(4, 7, 'teach', 'Advanced'),
(4, 5, 'learn', 'Intermediate'),
-- Sneha teaches Spanish (Expert) & Public Speaking (Advanced), wants to learn Java (Beginner)
(5, 11, 'teach', 'Expert'),
(5, 10, 'teach', 'Advanced'),
(5, 1, 'learn', 'Beginner'),
-- Krushnakant teaches PHP & MySQL (Expert), Java (Advanced)
(6, 5, 'teach', 'Expert'),
(6, 1, 'teach', 'Advanced'),
(6, 8, 'learn', 'Intermediate'),
-- Swarali teaches Python (Advanced), Figma (Intermediate)
(7, 2, 'teach', 'Advanced'),
(7, 8, 'teach', 'Intermediate'),
(7, 5, 'learn', 'Beginner'),
-- Sakul teaches C++ (Advanced), Web Development (Intermediate)
(8, 3, 'teach', 'Advanced'),
(8, 4, 'teach', 'Intermediate'),
(8, 2, 'learn', 'Beginner'),
-- Suhani teaches Graphic Design (Advanced), UI/UX (Intermediate)
(9, 6, 'teach', 'Advanced'),
(9, 8, 'teach', 'Intermediate'),
(9, 1, 'learn', 'Beginner'),
-- Sanskruti teaches Data Science (Advanced), Python (Intermediate)
(10, 2, 'teach', 'Advanced'),
(10, 10, 'teach', 'Intermediate'),
(10, 6, 'learn', 'Beginner');

-- Seed Learning Requests
INSERT IGNORE INTO `learning_requests` (`id`, `learner_id`, `mentor_id`, `skill_id`, `message`, `status`) VALUES
(1, 3, 2, 1, 'Hi Rahul, I would love to learn core Java concepts from you in exchange for Graphic Design lessons!', 'accepted'),
(2, 5, 2, 1, 'Hey Rahul, could you teach me basic Java object-oriented programming?', 'pending'),
(3, 2, 4, 7, 'Hi Aman, looking forward to learning video editing basics!', 'accepted');

-- Seed Sessions
INSERT IGNORE INTO `sessions` (`id`, `request_id`, `learner_id`, `mentor_id`, `session_date`, `session_time`, `session_topic`, `room_id`, `status`, `learner_confirmed`, `mentor_confirmed`) VALUES
(1, 1, 3, 2, CURDATE(), '16:00:00', 'Java OOP Inheritance & Interfaces', 'ROOM-KEH-2-3-991', 'completed', 1, 1),
(2, 3, 2, 4, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '18:00:00', 'Premiere Pro Timeline & Transitions', 'ROOM-KEH-4-2-882', 'scheduled', 0, 0);

-- Seed Messages
INSERT IGNORE INTO `messages` (`sender_id`, `receiver_id`, `request_id`, `message`, `is_read`) VALUES
(3, 2, 1, 'Hi Rahul! Excited to start our Java learning session.', 1),
(2, 3, 1, 'Hey Krishna! Welcome. Let us meet today at 4 PM for Java OOP.', 1),
(3, 2, 1, 'Sounds great! I have my questions ready.', 1);

-- Seed Reviews
INSERT IGNORE INTO `reviews` (`session_id`, `reviewer_id`, `reviewed_user_id`, `rating`, `review_text`) VALUES
(1, 3, 2, 5, 'Rahul is an amazing mentor! Very patient and explained OOP concepts with clear code examples.');

-- Seed Points Log
INSERT IGNORE INTO `points` (`user_id`, `points`, `reason`) VALUES
(2, 10, 'Taught Java OOP session'),
(2, 3, 'Received 5-star review from Krishna'),
(3, 5, 'Completed Java learning session'),
(2, 5, 'Profile completion bonus'),
(3, 5, 'Profile completion bonus'),
(4, 5, 'Profile completion bonus');

SET FOREIGN_KEY_CHECKS = 1;
