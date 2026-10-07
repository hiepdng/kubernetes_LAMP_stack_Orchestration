# <div align="center">$${\color{red}Containerize \space and \space Orchestrate \space a  \space LAMP \space with \space Kubernetes}$$

<br/>

To focus on Kubernetes containerization and orchestration, I include a directory [docker_build](https://github.com/hiepdng/kubernetes_github_action_LAMP_stack_deployment/tree/main/docker_build), which is a pre-configured LAMP stack IaC from my other project [docker_build_DHI_LAMP_Project](https://github.com/hiepdng/docker_build_DHI_LAMP_Project). All you need to do is to use these code to build the LAMP stack images, then use Kubernetes to containerize and orchestrate the LAMP stack.  

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
git clone https://github.com/hiepdng/kubernetes_github_action_LAMP_stack_deployment.git
```
- **Login to dockerhub from your terminal**
```
docker login dhi.io
```
- **Configure/setup environment**  
  This will set up directories, create certificates and modify configuration files 
```
cd kubernetes_github_action_LAMP_stack_deployment/docker_build
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
minikube start --driver=docker \
  --addons=metrics-server \
  --mount --mount-string="/home/temp/kubernetes_github_action_LAMP_stack_deployment/docker_build:/myvol"

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

kubectl get configmaps -A    #list all configmaps
```

- **Apply the deployment:**
```
#Load all images from your local machine to Minikube cluster
minikube image load lamp-httpd:2.4.68-debian13
minikube image load lamp-php:8.5.8-debian13-fpm
minikube image load lamp-mysql:lts-debian13

minikube image list           #list all images

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
- **<ins>Set resource usage for a node when starting a cluster</ins>**:  
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
<br/>

- **<ins>Manually scale up/down infrastructure</ins>**:  
```
#Scale up:
kubectl get nodes                    #List all node names
minikube node add                    #Add a worker node
minikube node add --control-plane    #Add a control plane for high availability

#Scale down:
kubectl drain <node-name> --ignore-daemonsets
minikube node delete <node-name>
```
<br/>

- **<ins>Manually scale up/down deployments (application workloads)</ins>**:  
```
kubectl get deployment                                                     #list all deployment names
kubectl scale deployment/<deployment-name> --replicas=<number-of-pods>     #increase number of instances
kubectl scale deployment/lamp-httpd-frontend --replicas=3
kubectl scale deployment/lamp-php-fpm-frontend--replicas=3
kubectl scale deployment/lamp-mysql-backend --replicas=2
kubectl get pods                                                            #checking number of pods
```
<br/>

- **<ins>Manage resources</ins>**: CPUs, Memory  
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
<br/>

- **<ins>Horizontal Pod Autoscaling</ins>**:  
  A HorizontalPodAutoscaler (HPA) automatically updates workload resources like Deployments to adjust capacity based on demand. With horizontal scaling, the HPA automatically adds pods when demand goes up and removes them when demand drops.

  Below is an example of auto-scaling pods for the httpd, php-fpm  and mysql deployments. You can configure auto-scaling for httpd and php-fpm or mysql deployment alone.

  The following are steps to enable HPA:

  - Step 1: Enable Metrics Server  
    Enable the metrics-server addon if you did not do so when running the minikube start command.
    ```
    minikube addons enable metrics-server                  #enable metrics-server
    kubectl get apiservices                                #check v1beta1.metrics.k8s.io service is available
    kubectl get pods -n kube-system | grep metrics-server  #check if metrics-server is running 
    minikube addons list                                   #check if the metrics-server addon is enable
    ```
  - Step 2: Create a Deployment with Resource Requests  
    Your pods must define CPU or memory requests so the HPA knows when to scale. The below are examples of the httpd, php-fpm and mysql deployments that define request resources:  
    ```yaml
    k8s_lampstack_deploy.yaml

    ...
      containers:
        - name: httpd
          image: lamp-httpd:2.4.68-debian13
          imagePullPolicy: Never
          resources:
            requests:
                cpu: "50m"
                memory: "128Mi"
            limits:
                cpu: "100m"
                memory: "256Mi"
    ...
      containers:
        - name: php-fpm
          image: amp-php:8.5.8-debian13-fpm
          imagePullPolicy: Never
          resources:
            requests:
                cpu: "50m"
                memory: "128Mi"
            limits:
                cpu: "100m"
                memory: "256Mi"
    ...
      containers:
        - name: mysql
          image: lamp-mysql:lts-debian13
          imagePullPolicy: Never
          resources:
            requests:
                cpu: "50m"
                memory: "512Mi"
            limits:
                cpu: "100m"
                memory: "1024Mi"
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
    There are two main methods of horizontal autoscaling:
    
    • <ins>Using command line</ins>:  
    ```
    kubectl autoscale deployment lamp-httpd-frontend --cpu=50% --memory=50% --min=1 --max=3
    kubectl autoscale deployment lamp-php-fpm-frontend --cpu=50% --memory=50% --min=1 --max=3
    kubectl autoscale deployment lamp-mysql-backend --cpu=50% --memory=80% --min=1 --max=2
    ```
    or  
    • <ins>Using a manifest file</ins>:
     ```yaml
     k8s_hpa.yaml
     
     ---
     apiVersion: autoscaling/v2
     kind: HorizontalPodAutoscaler
     metadata:
       name: lamp-httpd-frontend
     spec:
       scaleTargetRef:
         apiVersion: apps/v1
         kind: Deployment
         name: lamp-httpd-frontend
       minReplicas: 1
       maxReplicas: 3
       metrics:
       - type: Resource
         resource:
           name: cpu
           target:
             type: Utilization
             averageUtilization: 50
       - type: Resource
         resource:
           name: memory
           target:
             type: Utilization
             averageUtilization: 50

     ---
     apiVersion: autoscaling/v2
     kind: HorizontalPodAutoscaler
     metadata:
       name: lamp-php-fpm-frontend
     spec:
       scaleTargetRef:
         apiVersion: apps/v1
         kind: Deployment
         name: lamp-php-fpm-frontend
       minReplicas: 1
       maxReplicas: 3
       metrics:
       - type: Resource
         resource:
           name: cpu
           target:
             type: Utilization
             averageUtilization: 50
       - type: Resource
         resource:
           name: memory
           target:
             type: Utilization
             averageUtilization: 50
     
     ---
     apiVersion: autoscaling/v2
     kind: HorizontalPodAutoscaler
     metadata:
       name: lamp-mysql-backend
     spec:
       scaleTargetRef:
         apiVersion: apps/v1
         kind: Deployment
         name: lamp-mysql-backend
       minReplicas: 1
       maxReplicas: 2
       metrics:
       - type: Resource
         resource:
           name: cpu
           target:
             type: Utilization
             averageUtilization: 50
       - type: Resource
         resource:
           name: memory
           target:
             type: Utilization
             averageUtilization: 80
     ```
    Apply the HorizontalPodAutoscaler
    ```bash
    kubectl apply --validate=true -f k8s_hpa.yaml
    ```
  - Step 4: Verifying
    ```
    kubectl get all -A                                         #display basic, workload-related resources
    kubectl get hpa                                            #check HPA Status
    kubectl describe hpa lamp-httpd-frontend                   #inspect Detailed Conditions
    kubectl get apiservice v1beta1.metrics.k8s.io              #verify vetrics-server availability
    kubectl get pods -n kube-system -l k8s-app=metrics-server  #verify if metrics-server is running
    kubectl top pods                                           #show resource usage
    kubectl logs -n kube-system deployment/metrics-server      #show metrics-server log
    ```
<br/>

- **<ins>Load Balancer</ins>**:  
  In Minikube, the purpose of a LoadBalancer service type is to simulate a cloud-provider load balancer so you can expose your local Kubernetes applications externally and distribute incoming traffic across multiple pods.

  LoadBalancer can be used with HorizontalScaling. If number of replicas of deployments is more than 2, it will route network traffic to those Pods and automatically load balance traffic between them.  
   
  - Expose the deployment _lamp-httpd-frontend_ using type LoadBalancer.
    - Using command line:
    ```
    kubectl expose deployment <deployment-name> --type="LoadBalancer"
    kubectl expose deployment lamp-httpd-frontend --type="LoadBalancer"
    ```
    or
    - Using manifest file:
    ```yaml
    k8s_loadbalance_service.yaml
    
    apiVersion: v1
    kind: Service
    metadata:
      name: lamp-httpd-frontend     #deployment name
      labels:
        app: lamp
      namespace: default
    spec:
      type: LoadBalancer
      ports:
        - name: http
          port: 8080
          targetPort: 8080
        - name: https
          port: 443
          targetPort: 443
      selector:
        app: lamp
        tier: frontend
    ```
    ```
    kubectl apply --validate=true -f k8s_loadbalance_service.yaml
    ```
   - Run the Tunnel:  
     Running `minikube tunnel` creates a network route from your host operating system directly to the cluster's LoadBalancer service IP.
     ```
     minikube tunnel
     ```
   - Auto-Opens a Browser Window:  
     Run the command below to auto open a web browser that access your application deployment.
     ```
     minikube service <service-name>
     minikube service lamp-httpd-frontend
     ```
     Or you can get its url from the below command:
     ```
     $ minikube service lamp-httpd-frontend --url
 
     http://192.168.49.2:30270     #example output 
     ```
<br/>

- **<ins>Self-Healing</ins>**:  
  Kubernetes will automatically restarts failed containers, replaces unhealthy ones, and reschedules them when a server breaks.
  
  - To test the Self-Healing feature, run _lamp-httpd-frontend_ deployment as two replicas
  ```yaml
  k8s_lampstack_deploy.yaml
  
  apiVersion: apps/v1
  kind: Deployment
  metadata:
    name: lamp-httpd-frontend
    labels:
      app: lamp
  spec:
    replicas: 2
  ```
  - Update the deployment:
  ```
  kubectl apply --validate=true -f k8s_lampstack_deploy.yaml
  ```
  - Delete a pod:
  ```yaml
  kubectl get pods
  NAME                                     READY   STATUS    RESTARTS   AGE
  lamp-httpd-frontend-564cdc8b46-jtgjf     1/1     Running   0          14m
  lamp-httpd-frontend-564cdc8b46-nnm6c     1/1     Running   0          11m

  kubectl delete pod lamp-httpd-frontend-564cdc8b46-jtgjf
  ```
  - Verify Self-Healing feature:
    Kubernetes will automatically create a new pod to ensure that two replicas of the _lamp-httpd-frontend_ deployment are running.
  ```yaml
  kubectl get pods
  NAME                                     READY   STATUS    RESTARTS   AGE
  lamp-httpd-frontend-564cdc8b46-nnm6c     1/1     Running   0          16m
  lamp-httpd-frontend-564cdc8b46-w6d7b     1/1     Running   0          6s
  ```
<br/>

- **<ins>Automated Rollouts, Updates and Rollbacks</ins>**:  
  You can test Kubernetes rollouts, updates, and rollbacks locally using minikube by deploying an application, updating its image version, and undoing the deployment if problems arise.

  #### Start Minikube and Create a Deployment:  
  - Start your local cluster:  
  ```
  minikube start
  ```
  - Create an initial deployment (e.g., Nginx version 1.14):  
  ```
  kubectl create deployment nginx-app --image=nginx:1.14
  ```
  - Expose the deployment as a NodePort service:  
  ```
  kubectl expose deployment nginx-app --type=NodePort --port=80
  ```
  #### Trigger a Rolling Update (Rollout):  
  - Update the container image to a newer version (e.g., 1.16). Kubernetes handles this as a zero-downtime rolling update:  
  ```
  kubectl set image deployment/nginx-app nginx=nginx:1.16
  ```
  - Monitor the rollout progress in real time:  
  ```
  kubectl rollout status deployment/nginx-app
  ```
  - Check the rollout revision history:  
  ```
  kubectl rollout history deployment/nginx-app
  ```
  #### Rollback to a Stable Version:  
  - If a newly updated image introduces a bug or fails (such as an ImagePullBackOff error), you can instantly revert the deployment to the previous stable revision:  
  ```
  kubectl rollout undo deployment/nginx-app
  ```
  - To roll back to a specific revision number (e.g., revision 1), specify it explicitly:
  ```
  kubectl rollout undo deployment/nginx-app --to-revision=1
  ```
  
<br/>

- **<ins>Storage Orchestration</ins>**:  
  Automatically mounts storage systems of your choice—such as local storage or public cloud providers—to your containers.
  
  - Create a PersistentVolume:  
    As cluster administrator, create a PersistentVolume backed by physical storage. and do not associate the volume with any Pod.
    
    ```yaml
    apiVersion: v1
    kind: PersistentVolume
    metadata:
      name: task-pv-volume
      labels:
        type: local
    spec:
      storageClassName: manual
      capacity:
        storage: 10Gi
      accessModes:
        - ReadWriteOnce
      hostPath:
        path: "/mnt/data"
    ```
  - Create a PersistentVolumeClaim:  
    As a developer/cluster user, create a PersistentVolumeClaim that is automatically bound to a suitable PersistentVolume.

    ```yaml
    apiVersion: v1
    kind: PersistentVolumeClaim
    metadata:
      name: task-pv-claim
    spec:
      storageClassName: manual
      accessModes:
        - ReadWriteOnce
      resources:
        requests:
          storage: 3Gi
    ```







<br/>
<br/>
<br/>
<br/>

---

#### Some other commands for debuging:
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
kubectl logs lamp-mysql-backend-5bc9957c6c-pkpqw -c mysql
kubectl logs lamp-php-fpm-frontend-64ff899c5d-px298 -c php-fpm
kubectl describe pod lamp-frontend-7bf4f58756-tpsjs


#Watch live events
kubectl get pods -w
kubectl get events -w
kubectl events -w --all-namespaces
watch kubectl top pods



kubectl scale deployment lamp-mysql --replicas=0    #stop lamp-mysql app
```
<br/>

#### Kubernetes Clean up:
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


