const { spawnSync } = require( 'child_process' );
const { rmdirSync } = require( 'fs' );
const tmp = require( 'tmp' );

const REPO = 'git@github.com:ithemes/restrict-content-pro.git';

function run( command, args, options ) {
    console.log( `${command} ${args.join( ' ' )}` );
    const retval = spawnSync( command, args, options );

    if ( retval.error ) {
        throw retval.error;
    }

    if ( retval.status !== 0 ) {
        throw new Error();
    }

    return retval;
}

(async () => {
    const { name: tempDir } = tmp.dirSync();

    run( 'git', ['clone', REPO, './'], {
        cwd  : tempDir,
        stdio: "inherit"
    } );

    if ( process.argv.length === 3 ) {
        run( 'git', ['checkout', process.argv[2]], {
            cwd  : tempDir,
            stdio: "inherit"
        } );
    }
    run( 'rsync', ['-a', '--delete', `${tempDir}/core/`, './core'], { stdio: "inherit" } );
    rmdirSync( tempDir, { recursive: true } );

    run( 'npm', ['ci'], { stdio: "inherit" } );
    run( 'npm', ['run', 'build'], { stdio: "inherit" } );

    console.log( 'Update completed' );
})().catch( ( e ) => {
    console.error( e );
    process.exit( 1 );
} );
