pipeline {
    agent none

    options {
          timeout(time: 20, unit: 'MINUTES')
    }
    stages {
        // uncomment for a one-time action to clear caches in docker volumes
        // stage('Clear-volume-cache') {
        //     agent any
        //     steps {
        //         script {
        //             // sh 'docker volume rm -f phpstan-cache'
        //             // sh 'docker volume rm -f composer-cache'
        //         }
        //     }
        // }
        // uncomment for a one-time action to clear caches in bind volumes
        // stage('Clear-bind-cache') {
        //     agent {
        //         docker {
        //             image 'library/alpine:latest'
        //             args '--user root:root --volume "/:/realroot" '
        //         }
        //     }
        //     steps {
        //         script {
        //             cleanWs()
        //             sh 'rm -rf /tmp/gomod'
        //         }
        //     }
        // }
        stage('Go build') {
            agent {
                docker {
                    image docker.build("storj-ci", "--pull https://github.com/storj/ci.git#main").id
                    args '--user root:root --volume "/tmp/gomod":/go/pkg/mod '
                }
            }
            steps {
                script {
                    sh './build.sh'
                    stash(name: "build", includes: "build/")
                }
            }
            post {
                always {
                    cleanWs()
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
            post {
                always {
                    cleanWs()
                }
            }
        }
        stage('Composer') {
            agent {
                docker {
                    image 'composer:2.0.11'
                    args '--mount type=volume,source=composer-cache2,destination=/root/.composer/cache '
                }
            }
            steps {
                sh 'composer install --ignore-platform-reqs'
                stash(name: "vendor", includes: "vendor/")
            }
            post {
                always {
                    cleanWs()
                }
            }
        }
        stage('PHPStan') {
            agent {
                docker {
                    image 'ghcr.io/phpstan/phpstan:1.0.2'
                    args '--mount type=volume,source=phpstan-cache,destination=/tmp/phpstan ' +
                        '--user root:root ' +
                        "--entrypoint='' "
                }
            }
            steps {
                unstash "vendor"
                sh 'phpstan analyse'
            }
            post {
                always {
                    cleanWs()
                }
            }
        }
        stage('PHPUnit') {
            agent {
                dockerfile {
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
            post {
                always {
                    cleanWs()
                }
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
