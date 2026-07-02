<?php
return [
    // Login
    'login_title' => 'SQLite Admin - Login',
    'sqlite_admin' => 'SQLite Admin',
    'server' => 'Server',
    'password' => 'Access Password',
    'connect' => 'Connect',
    'invalid_password' => 'Invalid password.',
    'invalid_server' => 'Invalid server selection.',
    'dirs_not_exist' => 'None of the configured directories exist.',

    // Navigation
    'mysql' => 'MySQL',
    'mongodb' => 'MongoDB',
    'logout' => 'Logout',
    'loading' => 'Loading...',
    'databases' => 'Databases',
    'browse' => 'Browse',
    'structure' => 'Structure',
    'query' => 'Query',
    'export' => 'Export',
    'drop' => 'Drop',
    'prev' => 'Prev',
    'next' => 'Next',

    // Buttons
    'create_database' => 'Create Database',
    'create_table' => 'Create Table',
    'add_column' => '+ Add Column',
    'execute' => 'Execute',
    'import' => 'Import',
    'insert' => 'Insert',
    'save' => 'Save',
    'cancel' => 'Cancel',
    'edit' => 'Edit',
    'delete' => 'Del',
    'back_to_tables' => 'Back to Tables',
    'insert_row' => 'Insert Row',

    // Table headers
    'name' => 'Name',
    'size' => 'Size',
    'path' => 'Path',
    'actions' => 'Actions',
    'type' => 'Type',
    'not_null' => 'Not NULL',
    'default_val' => 'Default',
    'pk' => 'PK',
    'unique' => 'Unique',
    'columns' => 'Columns',
    'column' => 'Column',
    'value' => 'Value',
    'null_val' => 'NULL',
    'yes' => 'YES',

    // Form
    'table_name' => 'Table name',
    'column_name' => 'Column name',
    'new_db_name' => 'New database name',
    'format' => 'Format',
    'paste_sql_here' => 'Paste SQL here...',

    // Info / Results
    'no_databases_found' => 'No SQLite database files found in the configured directories.',
    'no_tables_found' => 'No tables found.',
    'no_data_found' => 'No data found.',
    'query_no_results' => 'Query returned no results.',
    'rows_returned' => '%d row(s) returned in %s ms',
    'query_success' => 'Query executed successfully. %d row(s) affected. (%s ms)',
    'max_export_rows' => 'Max 10,000 rows per table for SQL/CSV exports.',
    'rows' => 'rows',

    // Confirm dialogs
    'confirm_drop_table' => "Drop table '%s'?",
    'confirm_delete_row' => 'Delete this row?',

    // Success messages
    'db_created' => 'Database "%s" created.',
    'table_created' => 'Table "%s" created.',
    'table_dropped' => 'Table "%s" dropped.',
    'row_deleted' => 'Row deleted.',
    'row_inserted' => 'Row inserted.',
    'row_updated' => 'Row updated.',
    'sql_imported' => 'SQL file imported successfully.',
    'sql_executed' => 'SQL executed successfully.',

    // Error messages
    'db_name_required' => 'Database name is required.',
    'invalid_directory' => 'Invalid directory.',
    'column_required' => 'At least one column is required.',
    'file_read_error' => 'Failed to read uploaded file or file is empty.',
    'no_sql_provided' => 'No file or SQL provided.',
    'row_not_found' => 'Row not found.',
    'not_authenticated' => 'Not authenticated',
    'post_required' => 'POST required',
    'missing_params' => 'Missing required parameters',
    'csrf_mismatch' => 'CSRF token mismatch',
    'error_scanning' => 'Error scanning %s: %s',

    // Page headings
    'tables_in' => 'Tables in',
    'structure_title' => 'Structure:',
    'query_title' => 'Query',
    'export_title' => 'Export',
    'import_into' => 'Import into',
    'edit_row' => 'Edit Row',
    'create_statement' => 'CREATE Statement',
    'indexes' => 'Indexes',

    // Export
    'upload_sql_file' => 'Upload SQL File',
    'paste_sql' => 'Paste SQL',
    'sql_dump_label' => 'SQL Dump (CREATE + INSERT statements)',
    'csv_label' => 'CSV',
    'csv_first_table' => '(first table only)',
    'download_db' => 'Download .db file',
];
