pipeline {
    agent none

    options {
          timeout(time: 20, unit: 'MINUTES')
    }
    stages {
        stage('Go build') {
            agent {
                docker {
                    alwaysPull true
                    image docker.build("storj-ci", "--pull https://github.com/storj/ci.git").id
                    args '--cap-add SYS_PTRACE -v "/tmp/gomod":/go/pkg/mod '
                }
            }
            steps {
                script {
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
//                         '-u root:root ' +
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
                docker {
                    // there is a permission error when building from a local Dockerfile
                    image docker.build("phpunit-storj", "--pull https://github.com/storj-thirdparty/uplink-php.git#jenkins").id
//                     args '--user root:root '
                }
            }
            steps {
                unstash "vendor"
                unstash "build"
                sh 'service postgresql start'
                sh '''su -s /bin/bash -c "psql -U postgres -c 'create database teststorj;'" postgres'''
                sh 'PATH="/root/go/bin:$PATH" && storj-sim network setup --postgres=postgres://postgres@localhost/teststorj?sslmode=disable'
                sh 'PATH="/root/go/bin:$PATH" && storj-sim network test printenv'
//                sh 'PATH="/root/go/bin:$PATH" && storj-sim network test ./vendor/bin/phpunit test/'
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
