#!/bin/bash
composer config github-oauth.github.com $GLOBAL_GITHUB_TOKEN

REPO_SUFFIX=""
if [[ $GITHUB_REPOSITORY == *"internal"* ]] ; then
    REPO_SUFFIX="-internal"
fi

if [[ $(curl -s -u ${GITHUB_ACTOR}:$GLOBAL_GITHUB_TOKEN https://api.github.com/repos/MultiSafepay/php-sdk${REPO_SUFFIX}/branches | grep -iGc '"name": "'${CURRENT_HEAD_REF}'"') == 1 ]]; then
    BRANCH_NAME=${CURRENT_HEAD_REF}
else
    BRANCH_NAME="master"
fi

git clone -b ${BRANCH_NAME} --single-branch https://${GITHUB_ACTOR}:$GLOBAL_GITHUB_TOKEN@github.com/MultiSafepay/php-sdk${REPO_SUFFIX}.git ./package-source/multisafepay/php-sdk/

composer config repositories.multisafepay "path" "package-source/multisafepay/*"

composer config minimum-stability dev
composer config prefer-stable false
composer update

composer require yireo/magento2-replace-bundled:^4.1 --no-update
