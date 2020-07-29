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
        // need to go from declarative to scripted pipeline
        // for sidecar container

        stage('PHPUnit') {
            agent {
                label 'main'
            }
            steps {
                unstash "vendor"
                unstash "build"
                script {
                    docker.build("storj-ci", "--pull https://github.com/storj/ci.git")
                        .withRun("-v /tmp/gomod:/go/pkg/mod -p 10000:10000 --mount type=bind,src=$WORKSPACE,dst=/parent", ''' sh -c "
                            service postgresql start
                            && psql -U postgres -c 'create database teststorj;'
                            && git clone https://github.com/storj/storj.git storj
                            && cd storj
                            && make install-sim
                            && storj-sim network setup --host storj-ci --postgres=postgres://postgres@localhost/teststorj?sslmode=disable
                            && storj-sim network env > ../dotenv
                            && storj-sim network run"
                            '''.replaceAll("\\s", ' ')
                        ) { container ->
                            // wait until storj-sim has started and environment variables are available
                            sh '''while [ ! -f dotenv ];
                                do sleep 1;
                                done;
                                sleep 60
                            '''
                            //sh "docker logs --follow ${container.id}"
                            docker.image('php:7.4-cli').inside("--user root:root --link ${container.id}:storj-ci") {\
                                sh '''
                                    apt-get update
                                    apt-get install -y libffi-dev
                                    docker-php-ext-install ffi
                                    export $(cat dotenv | xargs);
                                    export SATELLITE_ADDRESS=$SATELLITE_0_ID@storj-ci:10000
                                    export API_KEY=13YqgH45XZLg7nm6KsQ72QgXfjbDu2uhTaeSdMVP2A85QuANthM9K58ww5Y4nhMowrZDoqdA4Kyqt1ioQghQcm9fT5uR2drPHpFEqeb
                                    ./vendor/bin/phpunit test/
                                '''
                            }

                    }
                }
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
