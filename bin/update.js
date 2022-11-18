const { spawnSync } = require( 'child_process' );
const { rmdirSync } = require( 'fs' );
const tmp = require( 'tmp' );

const REPO = 'git@github.com:stellarwp/restrict-content-pro.git';

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

    clone_rcp(tempDir)

    checkout_branch(tempDir)

    // clone_submodule(tempDir)

    // Sinchronize the given branch with the current RC Files.
    run( 'rsync', ['-a', '--delete', `${tempDir}/core/`, './core'], { stdio: "inherit" } );
    // Delete the temporary folder that was created.
    rmdirSync( tempDir, { recursive: true } );

    print_success( 'Update completed' );
})().catch( ( e ) => {
    console.error( e );
    process.exit( 1 );
} );


function clone_rcp(tempDir) {
    // Execute the `git clone` in the current directory.
    run( 'git', ['clone', REPO, './'], {
        cwd  : tempDir, // cwd = current working directory
        stdio: "inherit"
    } );
}

function checkout_branch(tempDir) {
    // If the argument does not match it re repository will be in master.
    // Having 3 arguments means that we provided a branch to checkout and sync with that branch.
    if ( process.argv.length === 3 ) {
        run( 'git', ['checkout', process.argv[2]], {
            cwd  : tempDir,
            stdio: "inherit"
        } );
    }
}

function clone_submodule(tempDir) {
    run( 'git', ['submodule', 'update', '--init', '--recursive'], {
        cwd  : tempDir, // cwd = current working directory
        stdio: "inherit"
    } );
}


function print_success(message){
    console.log('\x1b[32m%s\x1b[0m', message)
}
