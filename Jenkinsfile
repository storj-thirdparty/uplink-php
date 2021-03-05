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
                    sh './build.sh'
                    stash(name: "build", includes: "build/")
                }
            }
        }
        stage('Release') {
            agent {
                docker {
                    image 'kramos/alpine-zip:latest'
                    args '--entrypoint ""'
                }
            }
            steps {
                unstash "build"
                sh "zip -r release.zip *"
                archiveArtifacts "release.zip"
            }
        }
        stage('Composer') {
            agent {
                docker {
                    image 'composer:2.0.11'
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
                    image 'phpstan/phpstan:0.12.80'
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
                    args '-u root:root'
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
