pipeline {
    agent none

    options {
          timeout(time: 20, unit: 'MINUTES')
    }
    stages {
        stage('Go build') {
            agent {
                docker {
                    label 'main'
                    image docker.build("storj-ci", "--pull https://github.com/storj/ci.git").id
                    args '--user root:root --cap-add SYS_PTRACE -v "/tmp/gomod":/go/pkg/mod '
                }
            }
            steps {
                script {
                    sh 'rm -rf tmp-c' // should have been cleaned up...
                    sh './build.sh'
                    stash(name: "build", includes: "build/")
                }
            }
        }
        stage('Composer') {
            agent {
                docker {
                    image 'composer:1.10.9'
                    args '--mount type=volume,source=composer-cache,destination=/root/.composer/cache '
                }
            }
            steps {
                sh 'composer install --ignore-platform-reqs'
                stash(name: "vendor", includes: "vendor/")
            }
        }
        stage('PHPStan') {
            agent {
                docker {
                    image 'phpstan/phpstan:0.12.33'
                    args '--mount type=volume,source=phpstan-cache,destination=/tmp/phpstan ' +
                        '-u root:root ' +
                        "--entrypoint='' "
                }
            }
            steps {
                unstash "vendor"
                sh 'phpstan analyse'
            }
        }
        stage('PHPUnit') {
            agent {
                dockerfile {
                    label 'main'
                    args '--user root:root root:root '
                }
            }
            steps {
                unstash "vendor"
                unstash "build"
                sh '''
                    ## API key is hardcoded in storj-sim
                    export API_KEY=13YqgH45XZLg7nm6KsQ72QgXfjbDu2uhTaeSdMVP2A85QuANthM9K58ww5Y4nhMowrZDoqdA4Kyqt1ioQghQcm9fT5uR2drPHpFEqeb
                    && export PATH="/root/go/bin:$PATH"
                    && service postgresql start
                    && su -s /bin/bash -c "psql -U postgres -c 'create database teststorj;'" postgres
                    && storj-sim network setup --postgres=postgres://postgres@localhost/teststorj?sslmode=disable
                    && storj-sim network test ./vendor/bin/phpunit test/
                '''.replaceAll("\\s", ' ')
            }
        }
    }
    post {
        always {
            node(null) {
                sh "chmod -R 777 ."
                deleteDir()
                cleanWs()
            }
        }
    }
}
