pipeline {
    agent none

    options {
          timeout(time: 20, unit: 'MINUTES')
    }
    stages {
        stage('Go build') {
            agent {
                docker {
                    image docker.build("storj-ci", "--pull https://github.com/storj/ci.git#main").id
                    args '--user root:root --cap-add SYS_PTRACE -v "/tmp/gomod":/go/pkg/mod '
                }
            }
            steps {
                script {
                    // need to keep deleting this directory because of some cleanup issue
                    sh 'rm -rf tmp-c'
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
                    image 'phpstan/phpstan:0.12.65'
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
                docker {
                    image docker.build("phpunit-storj").id
                    args '--user root:root '
                }
            }
            steps {
                unstash "vendor"
                unstash "build"
                sh 'service postgresql start'
                sh '''su -s /bin/bash -c "psql -U postgres -c 'create database teststorj;'" postgres'''
                sh 'PATH="/root/go/bin:$PATH" && storj-sim network setup --postgres=postgres://postgres@localhost/teststorj?sslmode=disable'
                // see https://github.com/storj/storj/wiki/Test-network#running-tests
                sh 'PATH="/root/go/bin:$PATH" && storj-sim network test ./vendor/bin/phpunit test/'
            }
        }
    }
    post {
        always {
            node(null) {
                cleanWs()
            }
        }
    }
}
