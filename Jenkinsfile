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
        stage('Go build x64') {
            agent {
                docker {
                    image 'golang:1.17.2'
                    args "--volume /tmp/gomod:/go/pkg/mod --user root:root"
                }
            }
            steps {
                script {
                    // get owner UID of working directory to run commands as that user
                    def userId = sh(script: "stat -c '%u' .", returnStdout: true).trim()
                    sh "useradd --create-home --uid ${userId} jenkins"

                    sh 'su jenkins -c "make build-x64"'
                    stash(name: "build-x64", includes: "build/")
                }
            }
            post {
                always {
                    cleanWs()
                }
            }
        }
        stage('Go build arm64') {
            agent {
                dockerfile {
                    dir 'docker/go-docker'
                    args '--volume /var/run/docker.sock:/var/run/docker.sock --volume /tmp/gomod:/go/pkg/mod --user root:root'
                }
            }
            steps {
                script {
                    // get owner UID of working directory to run commands as that user
                    def dockerGroupId = sh(script: "stat -c '%g' /var/run/docker.sock", returnStdout: true).trim()
                    def userId = sh(script: "stat -c '%u' .", returnStdout: true).trim()
                    // set group id of docker group to that of the host so we may access /var/run/docker.sock
                    sh "groupmod --gid ${dockerGroupId} docker"
                    sh "useradd --create-home --gid ${dockerGroupId} --uid ${userId} jenkins"
                    sh "newgrp docker"

                    sh 'su jenkins -c "make build-arm64"'
                    stash(name: "build-arm64", includes: "build/libuplink-aarch64-linux.so")
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
                unstash "build-x64"
                unstash "build-arm64"
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
                    image 'phpstan/phpstan:0.12.89'
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
                    dir 'docker/phpunit'
                    args '--user root:root '
                }
            }
            steps {
                unstash "vendor"
                unstash "build-x64"
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
