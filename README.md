# <div align="center">$${\color{red}Containerize \space and \space Orchestrate \space a  \space LAMP \space with \space Kubernetes}$$

<br/>

To focus on Kubernetes containerization and orchestration, I include a directory [docker_build](https://github.com/hiepdng/kubernetes_github_action_LAMP_stack_depoyment/tree/main/docker_build), which is a pre-configured LAMP stack IaC from my other project [docker_build_DHI_LAMP_Project](https://github.com/hiepdng/docker_build_DHI_LAMP_Project). All you need to do is to use these code to build the LAMP stack images, then use Kubernetes to containerize and orchestrate the LAMP stack.  

<br/>

### Prerequisite:
The following prerequisites must be available:
- **Docker Engine:** See [How to install Docker Linux (Ubuntu/RedHat)](https://docs.docker.com/engine/install/) for more information
- **Minikube Tool:** See [How to install minikube](https://docs.docker.com/engine/install/) for more information  
- **Registered Docker Account:** See [Docker account sign up](https://docs.docker.com/accounts/) for more information

<br/>

### Step 1: Building LAMP stack images on your local machine  
On a Linux machine, run the following commands to build LAMP stack images:

- **Download the repository**
```
git clone https://github.com/hiepdng/kubernetes_github_action_LAMP_stack_depoyment.git
```
- **Login to dockerhub from your terminal**
```
docker login dhi.io
```
- **Configure/setup environment**  
  This will set up directories, create certificates and modify configuration files 
```
cd kubernetes_github_action_LAMP_stack_depoyment/docker_build
sh setup.sh
```
- **Build httpd, mysql, php-fpm images**  
```
docker compose build --no-cache
```
This will create images:  
    . lamp-httpd:2.4.68-debian13  
    . lamp-php:8.5.8-debian13-fpm  
    . lamp-mysql:lts-debian13  

<br/>

### Step 2: Containerizing and Orchestrating a LAMP with Kubernetes  
- **Starts a local Kubernetes cluster inside a Docker container on your machine**  
  - You need to mount the full path of the **docker_build** directory from your host machine to Minikube cluster for Apache, PHP and Mysql applications to access their files. It is better to mount it here when you start your Minikube cluster.
  - Note: Your full path to the **docker_build** directory might be different.  
```
minikube start --driver=docker --mount --mount-string="/home/temp/kubernetes_github_action_LAMP_stack_deployment/docker_build:/myvol"

#or without mounting option
#note: Remember to keep the command 'minikube mount' running.
minikube start --driver=docker
minikube mount /home/temp/kubernetes_github_action_LAMP_stack_depoyment/docker_build:/tmp

```
- **Share external config file using ConfigMap:**  
Use ConfigMap to access httpd.conf, httpd-ssl.conf and php.ini from your host machine.

```
kubectl create configmap httpd-conf \
--from-file=httpd.conf=/home/temp/kubernetes_github_action_LAMP_stack_deployment/docker_build/etc/httpd.conf

kubectl create configmap httpd-ssl-conf \
--from-file=httpd-ssl.conf=/home/temp/kubernetes_github_action_LAMP_stack_deployment/docker_build/etc/httpd-ssl.conf

kubectl create configmap php-ini \
--from-file=php.ini=/home/temp/kubernetes_github_action_LAMP_stack_deployment/docker_build/etc/php.ini

kubectl create configmap my-conf \
--from-file=my.cnf=/home/temp/kubernetes_github_action_LAMP_stack_deployment/docker_build/etc/my.cnf
```

- **Apply the deployment:**
```
#Load all images from your local machine to Minikube cluster
minikube image load lamp-httpd:2.4.68-debian13
minikube image load lamp-php:8.5.8-debian13-fpm
minikube image load lamp-mysql:lts-debian13

#Apply the deployment
kubectl apply -f k8s_lampstack_deployment.yaml  
```
<br/>

### Access to the LAMP stack webpage:
Run the following command to forward httpd port 8080 from the Apache pod to your host port 8008
```
minikube service httpd-service
minikube tunnel                                     #For LoadBalancer services
kubectl port-forward svc/httpd-service 8008:8080    #pod:8080, host:8008
```
To access your webpage, goto http://127.0.0.1:8008

<br/>

### Checking:
```
minikube status                 #Checks the health of the local subsystem
minikube ip                     #Show the internal IP address of the Minikube cluster node. 
minikube profile list           #Shows active profiles and verifies your default target cluster
kubectl config current-context  #Show current Kubernetes cluster name
kubectl get po -A               #Lists the core system pods running across all namespaces.
kubectl cluster-info            #Display cluster information

docker images                   #List all docker images
docker ps -a                    #Lists running containers on your host machine.
                                 (eg. gcr.io/k8s-minikube/kicbase)

minikube dashboard              #Automatically opens a web-based Kubernetes user interface in your
                                 browser to view your cluster visually. 


kubectl get nodes               #Checks the status of the single node managed by Minikube.
kubectl get all                 #Show Services, Deployments, ReplicaSets, StatefulSets, DaemonSets, Jobs and CronJobs

kubectl get pods                   #List all pod names, Check pods health
kubectl get pods --show-labels     #List all pod names with lables
kubectl describe pods              #Describe all pods
kubectl describe pod <pod-name>    # Describe pod
kubectl describe pods -l app=lamp

minikube service list
kubectl get svc -A                 #List all services in all namespace. Check Existence & IPs
kubectl get svc                    #List all services in the current namespace. Check Existence & IPs
kubectl describe svc               #Describe all services. Check Routing (Endpoints)
kubectl describe svc service_name  #Describe service name. Check Routing (Endpoints)

minikube service httpd-service

kubectl get configmaps -A                      #Show all ConfigMaps
kubectl describe configmaps --all-namespaces   #Describe all ConfigMaps

#Debug:
kubectl logs deployment/lamp-frontend
kubectl logs deployment/lamp-mysql
kubectl logs lamp-frontend-7bf4f58756-tpsjs -c httpd

kubectl scale deployment lamp-mysql --replicas=0    #stop lamp-mysql app
```
<br/>

### Kubernetes Clean up:
- Clean Up All Workloads Across All Namespaces:
```
kubectl delete all --all --all-namespaces
```
- Reset Bare-Metal/Kubeadm Nodes:
```
sudo kubeadm reset
```


