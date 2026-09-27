# <div align="center">$${\color{red}Containerize \space and \space Orchestrate \space a  \space LAMP \space with \space Kubernetes}$$


To focus on Kubernetes containerization and orchestration, I include a directory [docker_build](https://github.com/hiepdng/kubernetes_github_action_LAMP_stack_depoyment/tree/main/docker_build), which is a pre-configured LAMP stack IaC from my other project [docker_build_DHI_LAMP_Project](https://github.com/hiepdng/docker_build_DHI_LAMP_Project). All you need to do is to use these code to build the LAMP stack images, then use Kubernetes to containerize and orchestrate the LAMP stack.  

<br/>

### Prerequisite:
The following must be available:
- Docker engine  
See [How to install Docker Linux (Ubuntu/RedHat)](https://docs.docker.com/engine/install/) for more information
- minikube tool   
See [How to install minikube](https://docs.docker.com/engine/install/) for more information  
- Docker account registered  
See [Docker account sign up](https://docs.docker.com/accounts/) for more information

<br/>

### Building LAMP stack images on your local machine:
On a Linux machine, run the following commands to build LAMP stack images:
```
git clone https://github.com/hiepdng/kubernetes_github_action_LAMP_stack_depoyment.git
cd kubernetes_github_action_LAMP_stack_depoyment/docker_build
docker login dhi.io
```

