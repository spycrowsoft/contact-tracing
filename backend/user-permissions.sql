
-- Create user for healthcare_worker interface and grant most restricted privileges.
CREATE USER 'healthcare_workers'@'localhost' IDENTIFIED BY 'password';

GRANT Update ON `contact-tracing`.healthcare_workers TO 'healthcare_workers'@'localhost';
GRANT Insert ON `contact-tracing`.healthcare_worker_sessions TO 'healthcare_workers'@'localhost';
GRANT Update ON `contact-tracing`.healthcare_worker_sessions TO 'healthcare_workers'@'localhost';
GRANT Select ON `contact-tracing`.view_retracted_keys TO 'healthcare_workers'@'localhost';
GRANT Select ON `contact-tracing`.view_healthcare_workers_logins TO 'healthcare_workers'@'localhost';
GRANT Select ON `contact-tracing`.view_healthcare_workers_active_sessions TO 'healthcare_workers'@'localhost';
GRANT Select ON `contact-tracing`.view_daily_tracing_key_submitted_by_healthcare_worker TO 'healthcare_workers'@'localhost';
GRANT Insert ON `contact-tracing`.daily_tracing_key_submission_requests TO 'healthcare_workers'@'localhost';
CREATE USER 'healthcare_workers'@'localhost';
FLUSH PRIVILEGES;

-- Create user for key submission and grant most restricted priviliges.
CREATE USER 'key_submit'@'localhost' IDENTIFIED BY 'password';
GRANT Select ON `contact-tracing`.view_key_submission_allowed TO 'key_submit'@'localhost';
GRANT Insert ON `contact-tracing`.active_daily_tracing_keys TO 'key_submit'@'localhost';
FLUSH PRIVILEGES;
