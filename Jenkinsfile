pipeline {
    agent none

    options {
          timeout(time: 26, unit: 'MINUTES')
    }
    stages {
        stage('Go build') {
            agent {
                docker {
                    label 'main'
                    image docker.build("storj-ci", "--pull https://github.com/storj/ci.git").id
                    args '-u root:root --cap-add SYS_PTRACE -v "/tmp/gomod":/go/pkg/mod '
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
            steps {
                unstash "vendor"
                unstash "build"
                sh './vendor/bin/phpunit test/'
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
