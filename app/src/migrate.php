<?php
/**
 * Database migrations: app/migrations/NNN_name.sql files run once each, in order, tracked in
 * vn_migrations. Runs automatically on the first request after an upload of a new version.
 */

declare(strict_types=1);

function vn_migration_files(): array {
	$files = glob( VN_APP . '/migrations/*.sql' ) ?: array();
	sort( $files );
	return $files;
}

/** Fast check (one cached kv read); runs pending migrations when the code is newer than the schema. */
function vn_maybe_migrate(): void {
	$want = count( vn_migration_files() ) . ':' . VN_VERSION;
	try {
		if ( kv_get( 'schema' ) === $want ) {
			return;
		}
	} catch ( PDOException $e ) {
		// Tables don't exist yet: fall through and create them.
	}
	vn_migrate();
	kv_set( 'schema', $want );
}

function vn_migrate(): array {
	$pdo = db();
	$pdo->exec( 'CREATE TABLE IF NOT EXISTS vn_migrations (name varchar(100) NOT NULL, ran_at datetime NOT NULL, PRIMARY KEY (name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' );
	$done = db_col( 'SELECT name FROM vn_migrations' );
	$ran  = array();
	foreach ( vn_migration_files() as $file ) {
		$name = basename( $file );
		if ( in_array( $name, $done, true ) ) {
			continue;
		}
		$sql = (string) file_get_contents( $file );
		$sql = preg_replace( '/^\s*--.*$/m', '', $sql ) ?? '';
		foreach ( array_filter( array_map( 'trim', explode( ';', $sql ) ) ) as $stmt ) {
			$pdo->exec( $stmt );
		}
		db_insert( 'vn_migrations', array( 'name' => $name, 'ran_at' => db_now() ) );
		$ran[] = $name;
	}
	return $ran;
}
