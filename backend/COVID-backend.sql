
--	Note that everything in this file is a suggestion.
--	In other words: Nothing in this file is definitive.

--	PoC MySQL database schema for use in the COVID-19 backend.

--	DROP-section
--	Here for convenience during development.

DROP VIEW IF EXISTS view_daily_tracing_key_submitted_by_healthcare_workers;
DROP VIEW IF EXISTS view_healthcare_workers_sessions;

DROP TABLE IF EXISTS active_daily_tracing_keys;

DROP TABLE IF EXISTS daily_tracing_key_submission_requests;

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

--	A table which keeps track of all account_admin logins

CREATE TABLE IF NOT EXISTS account_admin_sessions (
	account_admin_uuid VARCHAR(36) NOT NULL, -- account_admin_uuid of session
	session_token VARCHAR(36) NOT NULL, -- Token used to identify a session.
	active BOOLEAN NOT NULL DEFAULT FALSE, -- Whether or not this session is active.	
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
	hashed_password VARCHAR(64) NULL DEFAULT NULL, -- Salted password hash.
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

--	A table which keeps track of all healthcare_worker logins

CREATE TABLE IF NOT EXISTS healthcare_worker_sessions (
	healthcare_worker_uuid VARCHAR(36) NOT NULL, -- healthcare_worker_uuid of session
	session_token VARCHAR(36) NOT NULL, -- Token used to identify a session.	
	active BOOLEAN NOT NULL DEFAULT FALSE, -- Whether or not this session is active.
	begin_time TIMESTAMP NOT NULL DEFAULT NOW(), -- Time of session start.
	expiration_time TIMESTAMP NOT NULL DEFAULT DATE_ADD(NOW(), INTERVAL 30 MINUTE), -- Timestamp after which a session naturally expires if it has not been used.
	PRIMARY KEY (session_token),
	KEY (healthcare_worker_uuid, active, session_token),
	FOREIGN KEY (healthcare_worker_uuid) REFERENCES healthcare_workers(healthcare_worker_uuid) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS daily_tracing_key_submission_requests (
	request_uuid VARCHAR(36) NOT NULL DEFAULT UUID(), -- UUID of activation request.	
	healthcare_worker_uuid VARCHAR(36) NOT NULL, -- UUID of healthcare_worker who made the activation request.	
	creation_time TIMESTAMP NOT NULL DEFAULT NOW(), -- Time at which the request for daily_tracing_keys has been created.
	start_date DATE NOT NULL DEFAULT DATE_SUB(NOW(), INTERVAL 14 DAY), -- Start time from which we want to accept dtks (beginning of incubation period).
	end_date DATE NOT NULL DEFAULT DATE_ADD(NOW(), INTERVAL 14 DAY), -- Expiration timestamp of the healthcare_worker's request after which no new dtks will be accepted and the patient is assumed to be cured.
	submission_code VARCHAR(36) NOT NULL, -- Token which can be used by the app to submit daily_tracing_keys.	
	PRIMARY KEY (request_uuid),
	KEY (healthcare_worker_uuid, request_uuid),
	KEY (healthcare_worker_uuid, creation_time),
	KEY (creation_time),
	CHECK (end_date <= DATE_ADD(NOW(), INTERVAL 46 DAY)), -- Ensure that keys cannot be submitted beyond a into the future.
	CHECK (submission_code <> "000000000000000000000000000000000000"), -- Code with all zeros can be used for dummy-requests by phones, so we should not allow that one.
	UNIQUE (submission_code, creation_time),
	FOREIGN KEY (healthcare_worker_uuid) REFERENCES healthcare_workers(healthcare_worker_uuid) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS active_daily_tracing_keys (
	daily_tracing_key_uuid VARCHAR(36) NOT NULL DEFAULT UUID(), -- UUID of the daily tracing key.
	request_uuid VARCHAR(36) NOT NULL, -- UUID of activation request.
	submission_time TIMESTAMP NOT NULL DEFAULT NOW(), -- Time at which the daily_tracing_key has been received.	
	interval_number INTEGER UNSIGNED NOT NULL, -- Day number belonging to the daily_tracing_key.
	daily_tracing_key BINARY(16) NOT NULL, -- daily_tracing_key.
	retraction_time TIMESTAMP NULL DEFAULT NULL, -- Set to NOW() when the key has been retracted.
	PRIMARY KEY(daily_tracing_key_uuid),
	KEY(request_uuid, interval_number, daily_tracing_key),
	KEY (interval_number, daily_tracing_key),
	KEY (submission_time),
	KEY (submission_time, interval_number, daily_tracing_key),
	FOREIGN KEY (request_uuid) REFERENCES daily_tracing_key_submission_requests(request_uuid) ON UPDATE CASCADE ON DELETE CASCADE
);

--	Views for use by application.

--	View to be used to check if a healthcare_worker is currently logged in.

CREATE OR REPLACE VIEW view_healthcare_workers_active_sessions AS
SELECT 
	healthcare_workers.healthcare_worker_uuid AS healthcare_worker_uuid,
	healthcare_worker_sessions.session_token AS session_token
FROM
	healthcare_workers,
	healthcare_worker_sessions
WHERE
	healthcare_workers.healthcare_worker_uuid = healthcare_worker_sessions.healthcare_worker_uuid
	AND healthcare_workers.totp_seed IS NOT NULL
	AND healthcare_workers.hashed_password IS NOT NULL
	AND healthcare_workers.email IS NOT NULL
	AND healthcare_workers.phone_number IS NOT NULL
	AND healthcare_workers.reset_code IS NULL
	AND healthcare_workers.active IS TRUE
	AND healthcare_worker_sessions.active IS TRUE
	AND healthcare_worker_sessions.expiration_time > NOW()
	AND healthcare_workers.account_expiration_date > NOW();
    
--	View to show the healthcare_worker all daily_tracing_keys he or she has submitted.

CREATE OR REPLACE VIEW view_daily_tracing_key_submitted_by_healthcare_worker AS
SELECT
	dtksrs.healthcare_worker_uuid AS healthcare_worker_uuid,
	dtksrs.creation_time AS request_creation_time,
	adtks.submission_time AS submission_time,
	adtks.daily_tracing_key_uuid AS daily_tracing_key_uuid,
	adtks.interval_number AS interval_number, 
	adtks.daily_tracing_key AS daily_tracing_key,
	adtks.retraction_time AS retraction_time
FROM
	daily_tracing_key_submission_requests AS dtksrs,
	active_daily_tracing_keys AS adtks
WHERE
	dtksrs.request_uuid = adtks.request_uuid
	AND retraction_time IS NULL
	AND dtksrs.end_date > NOW();

--	View for the healthcare worker's login procedures

CREATE OR REPLACE VIEW view_healthcare_workers_logins AS
SELECT
	healthcare_workers.healthcare_worker_uuid AS healthcare_worker_uuid,
	healthcare_workers.username AS username,
	healthcare_workers.salt AS salt,
	healthcare_workers.hashed_password AS hashed_password,
	healthcare_workers.totp_seed AS totp_seed
FROM 
	healthcare_workers
WHERE
	healthcare_workers.totp_seed IS NOT NULL
	AND healthcare_workers.hashed_password IS NOT NULL
	AND healthcare_workers.email IS NOT NULL
	AND healthcare_workers.phone_number IS NOT NULL
	AND healthcare_workers.reset_code IS NULL
	AND healthcare_workers.active IS TRUE
	AND healthcare_workers.account_expiration_date > NOW();

--	View to export all active keys

CREATE OR REPLACE VIEW view_active_keys AS
SELECT 
	active_daily_tracing_keys.interval_number AS interval_number,
	active_daily_tracing_keys.daily_tracing_key AS daily_tracing_key,
	active_daily_tracing_keys.submission_time AS submission_time,
	active_daily_tracing_keys.retraction_time AS retraction_time 
FROM 
	active_daily_tracing_keys
WHERE
	retraction_time IS NULL
	AND submission_time < DATE_SUB(NOW(), INTERVAL 1 DAY)
ORDER BY interval_number ASC, daily_tracing_key ASC;

--	View to export all retracted keys
	
CREATE OR REPLACE VIEW view_retracted_keys AS
SELECT
	active_daily_tracing_keys.interval_number AS interval_number,
	active_daily_tracing_keys.daily_tracing_key AS daily_tracing_key,
	active_daily_tracing_keys.retraction_time AS retraction_time
FROM 
	active_daily_tracing_keys
WHERE
	active_daily_tracing_keys.retraction_time IS NOT NULL
	AND submission_time < DATE_SUB(NOW(), INTERVAL 1 DAY)
ORDER BY interval_number ASC, daily_tracing_key ASC;

-- View to check if a submission code is valid for key-submission.

CREATE OR REPLACE VIEW view_key_submission_allowed AS
SELECT
	request_uuid,
	submission_code
FROM
	daily_tracing_key_submission_requests
WHERE
	daily_tracing_key_submission_requests.end_date > NOW();

