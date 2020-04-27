
INSERT INTO healthcare_workers (healthcare_worker_uuid, username, hashed_password, salt, totp_seed, email, active, phone_number) VALUES ("b8e5f25d-88bd-11ea-b759-00113296ffa5", "healthworker1", "92d690d4eb4a598d5362f7196dba110e3974a9ea58eb9363be73e987d738afc6", "salt", "ADFASDSIGJASDF", "hw1@email.com", TRUE, "06-12345678");
INSERT INTO healthcare_workers (healthcare_worker_uuid, username, salt, email, phone_number) VALUES ("b90353e9-88bd-11ea-b759-00113296ffa5", "healthworker2", "salt2", "hw2@email.com", "06-87654321");

INSERT INTO daily_tracing_key_activation_requests (healthcare_worker_uuid, request_uuid, request_token) VALUES ("b8e5f25d-88bd-11ea-b759-00113296ffa5", "5d43c858-ff08-4e6c-9637-f829f6605d70", "123456789012345678901234567890123456" );

INSERT INTO active_daily_tracing_keys (request_uuid, interval_number, daily_tracing_key ) VALUES ("5d43c858-ff08-4e6c-9637-f829f6605d70", "2646692", 0x0800fc577294c34e4234235698593233);
INSERT INTO active_daily_tracing_keys (request_uuid, interval_number, daily_tracing_key ) VALUES ("5d43c858-ff08-4e6c-9637-f829f6605d70", "2646693", 0x0800fc577294c34e0b28ad2839435945);

UPDATE active_daily_tracing_keys set retraction_time=1 where daily_tracing_key_uuid = "76297827-88c9-11ea-b759-00113296ffa5";