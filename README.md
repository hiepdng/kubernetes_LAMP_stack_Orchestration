# <div align="center">$${\color{red}Containerize \space and \space Orchestrate \space a  \space LAMP \space with \space Kubernetes}$$


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
  - This will set up directories, create certificates and modify configuration files 
```
cd kubernetes_github_action_LAMP_stack_depoyment/docker_build
sh setup.sh
```
- **Build httpd, mysql, php-fpm images**
```
docker compose build --no-cache

```
<br/>

### Step 2: Containerizing and Orchestrating a LAMP with Kubernetes  
- **Provisions and starts a local Kubernetes cluster inside a Docker container on your machine**
```
minikube start --driver=docker
```

- **Apply the deployment:**
```
kubectl apply -f k8s_lampstack_deployment.yaml
```

- ""Checking:**
```

```







