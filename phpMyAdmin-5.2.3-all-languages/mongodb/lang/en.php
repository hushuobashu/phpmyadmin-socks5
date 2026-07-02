<?php
return [
    // Login
    'login_title' => 'MongoDB Admin - Login',
    'mongodb_admin' => 'MongoDB Admin',
    'server' => 'Server',
    'host' => 'Host',
    'port' => 'Port',
    'username' => 'Username',
    'password' => 'Password',
    'auth_database' => 'Auth Database',
    'connect' => 'Connect',
    'manual' => '-- Manual --',
    'optional' => 'optional',
    'invalid_server' => 'Invalid server selection.',

    // Navigation
    'mysql' => 'MySQL',
    'sqlite' => 'SQLite',
    'logout' => 'Logout',
    'loading' => 'Loading...',
    'databases' => 'Databases',
    'server_info' => 'Server Info',

    // Buttons
    'browse' => 'Browse',
    'query' => 'Query',
    'indexes' => 'Indexes',
    'stats' => 'Stats',
    'export' => 'Export',
    'drop' => 'Drop',
    'insert_document' => 'Insert Document',
    'create_collection' => 'Create Collection',
    'edit' => 'Edit',
    'delete' => 'Del',
    'execute' => 'Execute',
    'prev' => 'Prev',
    'next' => 'Next',
    'cancel' => 'Cancel',
    'save' => 'Save',
    'insert' => 'Insert',
    'update' => 'Update',
    'back_to_collections' => 'Back to Collections',

    // Table headers
    'name' => 'Name',
    'size' => 'Size',
    'type' => 'Type',
    'actions' => 'Actions',
    'collections' => 'Collections',
    'documents' => 'documents',

    // Form labels
    'filter' => 'Filter',
    'projection' => 'Projection',
    'sort' => 'Sort',
    'limit' => 'Limit',
    'skip' => 'Skip',
    'pipeline' => 'Pipeline (JSON array)',
    'collection_name' => 'New collection name',

    // Query
    'find' => 'Find',
    'aggregate' => 'Aggregate',
    'query_title' => 'Query',
    'documents_returned' => '%d document(s) returned',

    // Info / Results
    'no_documents_found' => 'No documents found.',
    'no_collections_found' => 'No collections found.',
    'collections_in' => 'Collections in',

    // Confirm dialogs
    'confirm_drop_collection' => "Drop collection '%s'?",
    'confirm_delete_document' => 'Delete this document?',
    'confirm_drop_index' => "Drop index '%s'?",
    'confirm_drop_database' => "Drop database '%s'?",

    // Success messages
    'collection_created' => 'Collection "%s" created.',
    'collection_dropped' => 'Collection "%s" dropped.',
    'document_deleted' => 'Document deleted.',
    'document_inserted' => 'Document inserted.',
    'document_updated' => 'Document updated.',
    'index_created' => 'Index created.',
    'index_dropped' => 'Index "%s" dropped.',

    // Error messages
    'invalid_json' => 'Invalid JSON: %s',
    'document_not_found' => 'Document not found.',
    'csrf_mismatch' => 'CSRF token mismatch',

    // Export
    'export_title' => 'Export',
    'format' => 'Format',
    'json_export' => 'JSON (one document per line)',
    'csv_export' => 'CSV',
    'max_export_docs' => 'Max 10,000 documents.',
    'download' => 'Download',

    // Indexes
    'indexes_title' => 'Indexes',
    'index_name' => 'Name',
    'index_keys' => 'Keys',
    'unique' => 'Unique',
    'sparse' => 'Sparse',
    'create_index' => 'Create Index',
    'drop_index' => 'Drop',
    'key_field' => 'Key field',
    'direction' => 'Direction',
    'ascending' => 'Ascending (1)',
    'descending' => 'Descending (-1)',
    'text' => 'Text',
    'add_key' => '+ Add Key',

    // Collection stats
    'collection_stats' => 'Collection Statistics',
    'stat_key' => 'Statistic',
    'stat_value' => 'Value',

    // Server info
    'build_info' => 'Build Info',
    'connections' => 'Connections',
    'opcounters' => 'Op Counters',
    'key' => 'Key',
    'value' => 'Value',

    // Document edit
    'edit_document' => 'Edit Document',
    'insert_new_document' => 'Insert New Document',
    'document_json' => 'Document (JSON)',

    // Page headings
    'yes' => 'YES',

    // Server Info page
    'server_information' => 'Server Information',
    'version' => 'Version',
    'git_version' => 'Git Version',
    'allocator' => 'Allocator',
    'javascript_engine' => 'JavaScript Engine',
    'bits' => 'Bits',
    'max_bson_size' => 'Max BSON Size',
    'current' => 'Current',
    'available' => 'Available',
    'total_created' => 'Total Created',
    'server_status' => 'Server Status',
    'uptime' => 'Uptime',
    'uptime_seconds' => '%s seconds',
    'process' => 'Process',

    // Collection stats page
    'stats_title' => 'Stats',
    'namespace' => 'Namespace',
    'document_count' => 'Document Count',
    'avg_doc_size' => 'Avg Document Size',
    'storage_size' => 'Storage Size',
    'total_index_size' => 'Total Index Size',
    'number_of_indexes' => 'Number of Indexes',
    'index_sizes' => 'Index Sizes',

    // Indexes page
    'indexes_on' => 'Indexes on',
    'field' => 'Field',
    'auto' => 'auto',

    // Export page
    'filter_optional_json' => 'Filter (optional, JSON)',
    'max_export_note' => 'Max 10,000 documents per export.',
];
