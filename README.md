# <div align="center">$${\color{red}Containerize \space and \space Orchestrate \space a  \space LAMP \space with \space Kubernetes}$$

<br/>

To focus on Kubernetes containerization and orchestration, I include a directory [docker_build](https://github.com/hiepdng/kubernetes_github_action_LAMP_stack_depoyment/tree/main/docker_build), which is a pre-configured LAMP stack IaC from my other project [docker_build_DHI_LAMP_Project](https://github.com/hiepdng/docker_build_DHI_LAMP_Project). All you need to do is to use these code to build the LAMP stack images, then use Kubernetes to containerize and orchestrate the LAMP stack.  

<br/>
<br/>

---  

# <div align="center">$${\color{blue} Setting \space up \space }$$  


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
minikube start --driver=docker
minikube mount /home/temp/kubernetes_github_action_LAMP_stack_deployment/docker_build:/tmp

```
- **Share external config file using ConfigMap:**  
Use ConfigMap to access httpd.conf, httpd-ssl.conf and php.ini from your host machine.

```
kubectl create configmap httpd-conf --from-file=httpd.conf=/home/temp/kubernetes_github_action_LAMP_stack_deployment/docker_build/etc/httpd.conf
kubectl create configmap httpd-ssl-conf --from-file=httpd-ssl.conf=/home/temp/kubernetes_github_action_LAMP_stack_deployment/docker_build/etc/httpd-ssl.conf
kubectl create configmap php-ini --from-file=php.ini=/home/temp/kubernetes_github_action_LAMP_stack_deployment/docker_build/etc/php.ini
kubectl create configmap my-cnf --from-file=my.cnf=/home/temp/kubernetes_github_action_LAMP_stack_deployment/docker_build/etc/my.cnf
```

- **Apply the deployment:**
```
#Load all images from your local machine to Minikube cluster
minikube image load lamp-httpd:2.4.68-debian13
minikube image load lamp-php:8.5.8-debian13-fpm
minikube image load lamp-mysql:lts-debian13

#Apply the deployment
kubectl apply --validate=true -f k8s_lampstack_deploy.yaml  
```
<br/>

### Step 3: Access to the LAMP stack webpage:
Run the following command to forward pod Apache port 8080 to your host port 8008
```
kubectl port-forward svc/httpd-service 8008:8080    #pod:8080, host:8008

or
minikube service httpd-service --url                #used with nodePort
minikube tunnel                                     #For LoadBalancer services
```
To access your webpage, goto http://127.0.0.1:8008

<br/>
<br/>

---  

# <div align="center">$${\color{blue}Managing \space Minikube \space Cluster}$$ 
### Basic Lifecycle Commands:
```
minikube start      #Start the cluster: Downloads images and boots the single-node environment
minikube status     #Check status: Shows the health of the host, kubelet, and API server
minikube stop       #Stop the cluster: Gracefully shuts down the underlying VM or container
minikube pause      #Pause Kubernetes: Temporarily freezes execution to save CPU
         unpause
minikube delete     #Delete the cluster: Wipes out the cluster instance and frees disk space
```

### Advanced Cluster Management:  
- Set resource usage for a node when starting a cluster: 
```
minikube stop
minikube start --cpus=4 --memory=8192mb --disk-size=50g  #for a single section

#or
minikube stop
minikube config set cpus 4                               #To permanently set a default limit
minikube config set memory 8192
minikube start

#Checking:
cat ~/.minikube/config/config.json
minikube config view
kubectl describe node minikube
```

- Manually scale up/down infrastructure:
```
#Scale up:
kubectl get nodes                    #List all node names
minikube node add                    #Add a worker node
minikube node add --control-plane    #Add a control plane for high availability

#Scale down:
kubectl drain <node-name> --ignore-daemonsets
minikube node delete <node-name>
```

- Manually scale up/down deployments (application workloads):
```
kubectl get deployment                                                     #list all deployment names
kubectl scale deployment/<deployment-name> --replicas=<number-of-pods>     #increase number of instances
kubectl scale deployment/lamp-httpd-frontend --replicas=3
kubectl scale deployment/lamp-php-fpm-frontend--replicas=3
kubectl scale deployment/lamp-mysql-backend --replicas=2
kubectl get pods                                                            #checking number of pods
```

- Manage resources: CPUs, Memory  
  By default, Kubernetes does not enforce resource requests or limits on deployments or their pods. This means a standard deployment can technically consume as much CPU and memory as the underlying minikube cluster provides before getting throttled or crashing. To prevent resource competition among pods, you can set maximum resource usage for each pod or deployment.
```
kubectl get deployment         #list all deployment names
kubectl get pods               #list all pod names

#Setting:
kubectl set resources deployment <deployment-name> --requests=cpu=200m,memory=512Mi --limits=cpu=500m,memory=1Gi
#or
kubectl patch deployment <deployment-name> --type=strategic \
  -p '{"spec":{"template":{"spec":{"containers":[{"name":"*","resources":{"requests":{"cpu":"200m","memory":"512Mi"},"limits":{"cpu":"500m","memory":"1Gi"}}}]}}}}'

#Unsetting:
kubectl patch deployment <deployment-name> --type json -p='[{"op": "remove", "path": "/spec/template/spec/containers/0/resources/requests/cpu"}]'
kubectl patch deployment <deployment-name> --type json -p='[{"op": "remove", "path": "/spec/template/spec/containers/0/resources/requests/memory"}]'
kubectl patch deployment <deployment-name> --type json -p='[{"op": "remove", "path": "/spec/template/spec/containers/0/resources/limits/cpu"}]'
kubectl patch deployment <deployment-name> --type json -p='[{"op": "remove", "path": "/spec/template/spec/containers/0/resources/limits/memory"}]'

#Verifying:
kubectl describe deployment <deployment-name>
```
- Horizontal Pod Autoscaling:  
A HorizontalPodAutoscaler (HPA) automatically updates workload resources like Deployments to adjust capacity based on demand. With horizontal scaling, the HPA automatically adds pods when demand goes up and removes them when demand drops. The following are steps to enable HPA:

  - Step 1: Enable Metrics Server
   ```
   minikube addons enable metrics-server         #enable metrics-server
   kubectl get apiservices                       #check v1beta1.metrics.k8s.io service is available
   minikube addons list                          #check if the metrics-server addon is enable
   ```
  - Step 2: Create a Deployment with Resource Requests  
    Your pods must define CPU or memory requests so the HPA knows when to scale. The below is the example of the httpd deployment:  
    ```yaml
    k8s_lampstack_deploy.yaml
    
      containers:
        - name: httpd
          image: lamp-httpd:2.4.68-debian13
          imagePullPolicy: Never
          resources:
            requests: "50m"
    ```
    Where:  
     &emsp;&emsp; • 1000m = 1 full CPU core  
     &emsp;&emsp; • 500m = 0.5 (half) of a CPU core  
     &emsp;&emsp; • 100m = 0.1 of a CPU core  
     &emsp;&emsp; • 50m = 0.05 of a CPU core
    
    Apply the deployment
    ```bash
    kubectl apply --validate=true -f k8s_lampstack_deploy.yaml 
    ```
    
  - Step 3: Configure Automatic Scaling (HPA)
    Use kubectl autoscale to automatically adjust the number of pods based on resource utilization. Run the autoscale command:  
    ```
    kubectl autoscale deployment lamp-httpd-frontend --cpu=50% --min=1 --max=5

    ```
    
<br/>


<br/>

### Checking:
```
#Minikube cluster info:
kubectl get pods -n kube-system #lists all the running and pending pods within the internal kube-system
minikube status                 #Checks the health of the local subsystem
minikube ip                     #Show the internal IP address of the Minikube cluster node. 
minikube profile list           #Shows active profiles and verifies your default target cluster
kubectl config current-context  #Show current Kubernetes cluster name
kubectl get po -A               #Lists the core system pods running across all namespaces.
kubectl cluster-info            #Display cluster information
kubectl get all                 #Show Services, Deployments, ReplicaSets, StatefulSets, DaemonSets, Jobs and CronJobs

docker images                   #List all docker images
docker ps -a                    #Lists running containers on your host machine.
                                 (eg. gcr.io/k8s-minikube/kicbase)

minikube dashboard              #Automatically opens a web-based Kubernetes user interface in your
                                 browser to view your cluster visually. 

#Node info:
kubectl get nodes               #Checks the status of the single node managed by Minikube.
minikube node list
kubectl describe node minikube


#Pod info:
kubectl get pods                   #List all pod names, Check pods health
kubectl get pods --show-labels     #List all pod names with lables
kubectl describe pods              #Describe all pods
kubectl describe pod <pod-name>    #Describe pod
kubectl describe pods -l app=lamp  #Describe pod, app name


#Services:
minikube service list
kubectl get svc -A                   #List all services in all namespace. Check Existence & IPs
kubectl get svc                      #List all services in the current namespace. Check Existence & IPs
kubectl describe svc                 #Describe all services. Check Routing (Endpoints)
kubectl describe svc s<ervice_name>  #Describe service name. Check Routing (Endpoints)
minikube service httpd-service
kubectl get svc <service_name>
kubectl get endpoints httpd-service   #Get endpoint service
minikube service <service-name> --url #Get the Connection URL for a Specific Service


#configMaps:
kubectl get configmaps -A                      #Show all ConfigMaps
kubectl describe configmaps --all-namespaces   #Describe all ConfigMaps
kubectl delete configmaps <config-name>


#Verifying the Mount
minikube ssh
ls -l myvol


#Volume:
kubectl get pvc
kubectl describe pvc mysql-pvc


#minikube images
minikube image load lamp-mysql:lts-debian13    #load local image into minikube
minikube image ls                              #List all images
minikube image rm <image_name>                 #Delete image


#Debug:
kubectl logs deployment/lamp-httpd-frontend
kubectl logs deployment/lamp-php-fpm-frontend
kubectl logs deployment/lamp-mysql-backend

kubectl logs service/php-fpm-service
kubectl logs service/httpd-service
kubectl logs service/mysql-service

kubectl get pod
kubectl logs lamp-frontend-7bf4f58756-tpsjs -c httpd
kubectl logs lamp-mysql-backend-5bc9957c6c-pkpqw -c lamp-mysql
kubectl logs lamp-php-fpm-frontend-64ff899c5d-px298 -c php-fpm
kubectl describe pod lamp-frontend-7bf4f58756-tpsjs


#Watch live events
kubectl get pods -w
kubectl get events -w
kubectl events -w --all-namespaces



kubectl scale deployment lamp-mysql --replicas=0    #stop lamp-mysql app
```
<br/>

### Kubernetes Clean up:
- Clean Up All Workloads Across All Namespaces:
```
kubectl get all                           #List pods, services, deployment, replica...

kubectl delete all --all --all-namespaces
kubectl delete  all --all --all-namespaces --interactive=false

kubectl delete deployment lamp-mysql-backend
kubectl delete pod lamp-mysql-backend-bb755b998-6tclg
kubectl delete service mysql-service
kubectl delete pvc mysql-pvc

minikube delete    #Delete the cluster: Wipes out the cluster instance and frees disk space

minikube delete --all --purge
#Note:.`minikube delete`**: Shuts down and deletes the local Kubernetes cluster, removing the VM or container.
      .`--all`**: Deletes all minikube profiles (clusters) you have created, not just the default one.
      .`--purge`**: Removes the `.minikube` directory from your user path, wiping out cached images, certificates, and leftover global configurations.
```
- Reset Bare-Metal/Kubeadm Nodes:
```
sudo kubeadm reset
```


