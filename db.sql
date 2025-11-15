CREATE DATABASE IF NOT EXISTS election;
USE election;

-- Positions table
CREATE TABLE IF NOT EXISTS positions (
  posID INT AUTO_INCREMENT PRIMARY KEY,
  posName VARCHAR(100) NOT NULL,
  numOfPositions INT DEFAULT 1, -- number of winners/slots for this position
  posStat ENUM('open','closed') DEFAULT 'open'
);

-- Voters table
CREATE TABLE IF NOT EXISTS voters (
  voterID VARCHAR(50) PRIMARY KEY, -- e.g. student number
  voterPass VARCHAR(255) NOT NULL, -- plain text for learning (not secure). Recommend hashing.
  voterFName VARCHAR(100),
  voterMName VARCHAR(100),
  voterLName VARCHAR(100),
  voterStat ENUM('active','inactive') DEFAULT 'active',
  voted ENUM('Y','N') DEFAULT 'N' -- 'Y' = already voted
);

-- Candidates table
CREATE TABLE IF NOT EXISTS candidates (
  candID INT AUTO_INCREMENT PRIMARY KEY,
  candFName VARCHAR(100),
  candMName VARCHAR(100),
  candLName VARCHAR(100),
  posID INT,
  candStat ENUM('active','inactive') DEFAULT 'active',
  FOREIGN KEY (posID) REFERENCES positions(posID) ON DELETE CASCADE
);

-- Votes table: records each vote cast (one row per candidate selected by voter & position)
CREATE TABLE IF NOT EXISTS votes (
  voteID INT AUTO_INCREMENT PRIMARY KEY,
  posID INT NOT NULL,
  voterID VARCHAR(50) NOT NULL,
  candID INT NOT NULL,
  voteTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (posID) REFERENCES positions(posID) ON DELETE CASCADE,
  FOREIGN KEY (voterID) REFERENCES voters(voterID) ON DELETE CASCADE,
  FOREIGN KEY (candID) REFERENCES candidates(candID) ON DELETE CASCADE
);