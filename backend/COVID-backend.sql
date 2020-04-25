
-- Note that everything in this file is a suggestion.
-- In other words: Nothing in this file is definitive.

-- PoC MySQL database schema for use in the COVID-19 backend.

-- DROP-section
-- Here for convenience during development.

DROP VIEW IF EXISTS view_daily_tracing_key_submitted_by_healthcare_workers;
DROP VIEW IF EXISTS view_healthcare_workers_sessions;

DROP TABLE IF EXISTS retracted_daily_tracing_keys;
DROP TABLE IF EXISTS active_daily_tracing_keys;

DROP TABLE IF EXISTS daily_tracing_key_activation_requests;

DROP TABLE IF EXISTS healthcare_worker_sessions;

DROP TABLE IF EXISTS account_admin_actions;

DROP TABLE IF EXISTS healthcare_workers;

DROP TABLE IF EXISTS account_admin_sessions;
DROP TABLE IF EXISTS account_admins;

--	account_admins table

--	This table contains the login data for account_admins.
--	An account admin can create a new username for a healthcare_worker, activate and deactivate accounts of healthcare_workers.
--	It should not do more as these are the "operational staff" of the system.

CREATE TABLE IF NOT EXISTS account_admins (
	account_admin_uuid VARCHAR(36) NOT NULL DEFAULT UUID(), -- Unique UUID of account admin.
	username VARCHAR(32) NOT NULL, -- Unique username of healthcare_worker
	hashed_password VARCHAR(64) NOT NULL, -- Salted password hash.
	salt VARCHAR(32) NOT NULL, -- account_admin specific salt for password.
	totp_seed VARCHAR(32) NULL DEFAULT NULL, -- account_admin specific seed for HMAC-TOTP authentication.
	email VARCHAR(255) NULL, -- E-mail address of healthcare_worker.
	phone_number VARCHAR(20) NULL, -- Phone number on which the account_admin has to be near-instantly available so we can reach them if their account shows suspicious activity.
	reset_code VARCHAR(64) NULL DEFAULT NULL, -- Password reset code.
	active BOOLEAN NOT NULL DEFAULT FALSE, -- Is account active?
	account_expiration_date DATE NOT NULL DEFAULT DATE_ADD(CURDATE(), INTERVAL 2 YEAR), -- Automatic expiration after 2 years.
	PRIMARY KEY (account_admin_uuid), 
	KEY (username),
	UNIQUE (username),
	KEY (email, reset_code),
	UNIQUE (email),
	UNIQUE (phone_number)
);

-- A table which keeps track of all account_admin logins

CREATE TABLE IF NOT EXISTS account_admin_sessions (
	account_admin_uuid VARCHAR(36) NOT NULL, -- account_admin_uuid of session
	active BOOLEAN NOT NULL DEFAULT FALSE, -- Wether or not this session is active.
	session_token VARCHAR(36) NOT NULL, -- Token used to identify a session.
	begin_time TIMESTAMP NOT NULL DEFAULT NOW(), -- Time of session start.
	expiration_time TIMESTAMP NOT NULL DEFAULT DATE_ADD(NOW(), INTERVAL 30 MINUTE), -- Timestamp after which a session naturally expires if it has not been used.
	PRIMARY KEY (account_admin_uuid, session_token),
	FOREIGN KEY (account_admin_uuid) REFERENCES account_admins(account_admin_uuid) ON UPDATE CASCADE ON DELETE CASCADE
);

--	The account_admin_actions table contains a log of all the actions performed by account_admins

CREATE TABLE IF NOT EXISTS account_admin_actions (
	account_admin_uuid VARCHAR(36) NOT NULL, -- account_admin_uuid of session.
	action_time TIMESTAMP NOT NULL DEFAULT NOW(), -- Time at which the action is performed.
	action_type VARCHAR(32) NOT NULL, -- The action performed.
	affected_uuid VARCHAR(36) NOT NULL, -- Unique UUID of healthcare_worker or account_admin the action is performed on.
	details_log TEXT NOT NULL, -- Detailed logging information of what the account_admin did.
	FOREIGN KEY (account_admin_uuid) REFERENCES account_admins(account_admin_uuid) ON UPDATE CASCADE ON DELETE CASCADE
);

--	healthcare_workers table

--	This table contains the data of the healthcare_workers whom are allowed to add new dtks for distribution.
--	This means that these users can register new infected persons.

--	For now, assume that every authorized health-care provider receives
--	their login-data by registered mail.

CREATE TABLE IF NOT EXISTS healthcare_workers (
	healthcare_worker_uuid VARCHAR(36) NOT NULL DEFAULT UUID(), -- Unique UUID of healthcare_worker.
	username VARCHAR(32) NOT NULL, -- Unique username of healthcare_worker
	hashed_password VARCHAR(64) NOT NULL, -- Salted password hash.
	salt VARCHAR(32) NOT NULL, -- healthcare_worker specific salt for password.
	totp_seed VARCHAR(32) NULL DEFAULT NULL, -- healthcare_worker specific seed for HMAC-TOTP authentication.
	email VARCHAR(255) NULL DEFAULT NULL, -- E-mail address of healthcare_worker.
	phone_number VARCHAR(20) NULL DEFAULT NULL, -- Phone number on which the healthcare_worker has to be near-instantly available so we can reach them if their account shows suspicious activity.
	reset_code VARCHAR(64) NULL DEFAULT NULL, -- Password reset code.
	active BOOLEAN NOT NULL DEFAULT FALSE, -- Is account active?
	account_expiration_date DATE NOT NULL DEFAULT DATE_ADD(CURDATE(), INTERVAL 2 YEAR), -- Automatic expiration after 2 years.
	PRIMARY KEY (healthcare_worker_uuid), 
	KEY (username),
	UNIQUE (username),
	KEY (email, reset_code),
	UNIQUE (email),
	UNIQUE (phone_number)
);

-- A table which keeps track of all heelthcare_worker logins

CREATE TABLE IF NOT EXISTS healthcare_worker_sessions (
	healthcare_worker_uuid VARCHAR(36) NOT NULL, -- healthcare_worker_uuid of session
	active BOOLEAN NOT NULL DEFAULT FALSE, -- Wether or not this session is active.
	session_token VARCHAR(36) NOT NULL, -- Token used to identify a session.
	begin_time TIMESTAMP NOT NULL DEFAULT NOW(), -- Time of session start.
	expiration_time TIMESTAMP NOT NULL DEFAULT DATE_ADD(NOW(), INTERVAL 30 MINUTE), -- Timestamp after which a session naturally expires if it has not been used.
	PRIMARY KEY (session_token),
	KEY (session_token, healthcare_worker_uuid),
	FOREIGN KEY (healthcare_worker_uuid) REFERENCES healthcare_workers(healthcare_worker_uuid) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS daily_tracing_key_activation_requests (
	request_uuid VARCHAR(36) NOT NULL DEFAULT UUID(), -- UUID of activation request.
	healthcare_worker_uuid VARCHAR(36) NOT NULL, -- UUID of healthcare_worker who made the activation request.
	valid_until TIMESTAMP NOT NULL DEFAULT DATE_ADD(NOW(), INTERVAL 14 DAY), -- Expiration timestamp of the healthcare_worker's session.
	request_token VARCHAR(36) NOT NULL, -- Single use token which can be used to submit daily_tracing_keys.
	time_received TIMESTAMP NULL DEFAULT NULL, -- Time at which the daily_tracing_keys have been received.
	PRIMARY KEY (request_uuid),
	KEY (request_uuid, healthcare_worker_uuid),
	KEY (time_received),
	FOREIGN KEY (healthcare_worker_uuid) REFERENCES healthcare_workers(healthcare_worker_uuid) ON UPDATE CASCADE ON DELETE NO ACTION
);

CREATE TABLE IF NOT EXISTS active_daily_tracing_keys (
	request_uuid VARCHAR(36) NOT NULL, -- UUID of activation request.
	interval_number UNSIGNED INTEGER NOT NULL, -- Day number belonging to the daily_tracing_key.
	daily_tracing_key BINARY(16) NOT NULL, -- daily_tracing_key.
	PRIMARY KEY(request_uuid, interval_number, daily_tracing_key),
	KEY (interval_number, daily_tracing_key),
	FOREIGN KEY (request_uuid) REFERENCES daily_tracing_key_activation_requests(request_uuid) ON UPDATE CASCADE ON DELETE NO ACTION
);

CREATE TABLE IF NOT EXISTS retracted_daily_tracing_keys (
	retraction_uuid VARCHAR(36) NOT NULL DEFAULT UUID(), -- uuid of the retraction-event.
	healthcare_worker_uuid VARCHAR(36) NOT NULL, -- uuid of the healthcare_worker who issued the retraction event.
	activation_request_uuid VARCHAR(36) NULL, -- uuid of the activation request the retracted daily_tracing_key appeared in.
	time_of_retraction TIMESTAMP NOT NULL DEFAULT NOW(), -- timestamp of when the retraction was issued.
	interval_number UNSIGNED INTEGER NOT NULL, -- Day number belonging to the daily_tracing_key.
	daily_tracing_key BINARY(16) NOT NULL, -- daily_tracing_key.
	PRIMARY KEY (retraction_uuid),
	FOREIGN KEY (healthcare_worker_uuid) REFERENCES healthcare_workers(healthcare_worker_uuid) ON UPDATE CASCADE ON DELETE NO ACTION,
	FOREIGN KEY (interval_number, daily_tracing_key) REFERENCES active_daily_tracing_keys(interval_number, daily_tracing_key) ON UPDATE CASCADE ON DELETE NO ACTION,
	FOREIGN KEY (activation_request_uuid) REFERENCES active_daily_tracing_keys(request_uuid) ON UPDATE CASCADE ON DELETE NO ACTION
);

-- Views for use by application.

-- View to be used to check if a healthcare_worker is currently logged in.

CREATE OR REPLACE VIEW view_healthcare_workers_sessions AS
SELECT 
	healthcare_workers.healthcare_worker_uuid AS healthcare_worker_uuid,
    healthcare_workers.username AS username,
    healthcare_workers.active AS healthcare_worker_active,
    healthcare_worker_sessions.active AS session_active,
    healthcare_worker_sessions.begin_time AS session_begin_time,
    healthcare_worker_sessions.expiration_time AS session_expiration_time
FROM
	healthcare_workers, healthcare_worker_sessions
WHERE
	healthcare_workers.healthcare_worker_uuid = healthcare_worker_sessions.healthcare_worker_uuid;

-- View to show the healthcare_worker all daily_tracing_keys he or she has submitted.

CREATE OR REPLACE VIEW view_daily_tracing_key_submitted_by_healthcare_workers AS
SELECT
	healthcare_workers.username AS username,
	dtkars.time_received AS time_received,
	adtks.interval_number AS interval_number,
    adtks.daily_tracing_key AS daily_tracing_key
FROM
	healthcare_workers,
	daily_tracing_key_activation_requests AS dtkars,
    active_daily_tracing_keys AS adtks
WHERE
	healthcare_workers.healthcare_worker_uuid = dtkars.healthcare_worker_uuid AND
    dtkars.request_uuid = adtks.request_uuid;
