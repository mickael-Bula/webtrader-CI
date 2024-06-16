## EXEMPLE DE FICHIER PRE-COMMIT

Exemple de fichier permettant de jouer les tests avant chaque commit.
Le chemin vers ces fichiers est `C:\laragon\www\webtrader_CI\.git\hooks` :

```bash
#!/bin/sh

#
# Run the hook command.
# Note: this will be replaced by the real command during copy.
#

# Fetch the GIT diff and format it as command input:
DIFF=$(git -c diff.mnemonicprefix=false -c diff.noprefix=false --no-pager diff -r -p -m -M --full-index --no-color --staged | cat)

# Grumphp env vars
export GRUMPHP_GIT_WORKING_DIR="$(git rev-parse --show-toplevel)"

# Run your tests
./vendor/bin/phpunit -c phpunit.xml
TEST_EXIT_STATUS=$?

# Check if tests failed
if [ $TEST_EXIT_STATUS -ne 0 ]; then
    echo "Tests failed. Commit aborted."
    exit 1
fi

# Run Codeception tests (run on pre-push only : too long)
./vendor/bin/codecept run
CODECEPT_EXIT_STATUS=$?

# Check if Codeception tests failed
if [ $CODECEPT_EXIT_STATUS -ne 0 ]; then
    echo "Codeception tests failed. Commit aborted."
    exit 1
fi

# Run GrumPHP
(cd "./" && printf "%s\n" "${DIFF}" | exec "vendor/bin/grumphp.bat" git:pre-commit '--skip-success-output')
GRUMPHP_EXIT_STATUS=$?

# Check if GrumPHP failed
if [ $GRUMPHP_EXIT_STATUS -ne 0 ]; then
    echo "GrumPHP checks failed. Commit aborted."
    exit 1
fi

# If everything passes, allow the commit to proceed
exit 0

```