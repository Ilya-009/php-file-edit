let files = [];
let selectedFiles = [];
let selectedCommands = [];
let messageHandlerState = false;

document.addEventListener('DOMContentLoaded', ()=>{
    getAllFilesRequest();

    // const request = new XMLHttpRequest();
    // let url = 'php/proceedProcess.php';
    // request.open('GET', url);
    // request.send();

    //Handler for start edit btn
    document.querySelector('#start-btn').addEventListener('click', ()=>{
        startEdit();
    });

    //Handlers of files checkboxes
    document.querySelectorAll('.task-check')
        .forEach(el => el.addEventListener('change', setCommandsSelectHandlers));

    //Handler of stop editing process
    document.querySelector('.stop-btn').addEventListener('click', ()=>{
        setActiveState(false);
    });
});

//Start btn clicked handler
const startEdit = ()=>{
    const request = new XMLHttpRequest();
    const url = 'php/queueService.php';

    const requestBody = preparePostData();
    if (requestBody == null) return;

    request.open('POST', url);
    request.send(requestBody);

    //Change state for optimization
    setActiveState(true);
    
    //Begin making requests to backend for messages
    notificationsHandler();
};

const setActiveState = state => {
    //Update local state for message handler
    messageHandlerState = state;

    const request = new XMLHttpRequest();
    const url = 'php/setProcessState.php?state=' + (state ? "1" : "0");
    request.open('GET', url);
    request.send();
};

const preparePostData = ()=>{
    let formData = new FormData();
    const fileCommands = document.querySelectorAll('.file-command');
    formData.append("files", selectedFiles.join(","));
    
    let commandsParams = [];

    selectedCommands.forEach(el => {
        //Select active commands
        if (fileCommands[el].children[1].children[0] != null){
            const commandParams = fileCommands[el].children[1].children[0].value;
            
            if (commandParams === ''){
                alert('Не введен параметр для выбранного шага!');
                return null;
            }

            commandsParams.push({number : el, value: commandParams});
        }else{
            commandsParams.push({number : el, value: ""});
        }
    });

    formData.append("commands", JSON.stringify(commandsParams));
    return formData;
};

const getAllFilesRequest = ()=>{
    const url = 'php/getAllFiles.php';
    const request = new XMLHttpRequest();
    request.open('GET', url);
    request.send();

    request.onload = ()=>{
        if (request.status !== 200){
            alert("Ошибка при соединении с сервером. Пожалуйста, повторите попытку");
            return;
        }

        files = JSON.parse(request.response);

        //Show files on UI
        showFiles(files);
    };
};

const setFileSelectHandlers = ()=>{
    document.querySelectorAll('.select-file-check').forEach(el => el.addEventListener('change', handleFileSelect));
};

//Function to handle file checkbox change event
const handleFileSelect = e =>{
    const currentCheckbox = e.target;
    const fileName = currentCheckbox.getAttribute("data");

    if (!currentCheckbox.checked){
        selectedFiles.splice(selectedFiles.findIndex(el => el === fileName), 1);
    }else{
        selectedFiles.push(fileName);
    }
};

const setCommandsSelectHandlers = e =>{
    const checkbox = e.target;
    const taskId = parseInt(checkbox.getAttribute('data'));

    if (selectedCommands.includes(taskId))
        selectedCommands.splice(selectedCommands.indexOf(taskId), 1);
    else
        selectedCommands.push(taskId);
};

const showFiles = files =>{
    let out = `<tr>
        <td></td>
        <td>Название файла</td>
        <td>Preview</td>
        <td>Разрешение</td>
        <td>Расширение файла</td>
        <td>Размер</td>
    </tr>`;

    files.forEach(file => {
        if (file.resolution != null){
            //File is image
            out += `<tr>
                    <td>
                        <input type="checkbox" class="select-file-check" data="${file.name}">
                    </td>
                    <td>${file.name}</td>
                    <td>
                        <img src="${window.location.href}/files/${file.name}" alt="">
                    </td>
                    <td>${file.resolution}</td>
                    <td>${file.name.split(".")[1]}</td>
                    <td>${file.size} байт</td></tr>`;
        }else{
            //File is document
            out += `<tr>
                    <td>
                        <input type="checkbox" class="select-file-check" data="${file.name}">
                    </td>
                    <td>${file.name}</td>
                    <td>${file.content}</td>
                    <td>-</td>
                    <td>${file.name.split(".")[1]}</td>
                    <td>${file.size} байт</td></tr>`;
        }
    });

    const newLine = document.createElement('tr');
    newLine.innerHTML = out;
    document.querySelector('.files').innerHTML = out;

    //Refresh triggers for next edits
    setFileSelectHandlers();
};

const notificationsHandler = ()=>{
    const request = new XMLHttpRequest();
    const url = 'php/notificationService.php';

    request.onload = ()=>{
        const response = request.response;
        if (response === "") return;

        if (response == 'end'){
            messageHandlerState = false;
            selectedFiles = [];
            return;
        }

        showMessageUI(response);

        if (messageHandlerState){
            notificationsHandler();
        }
    };

    request.open('GET', url);
    request.send();
};

const showMessageUI = message =>{
    const popup = document.querySelector('.notification-popup');
    const messageContentBlock = popup.children[0];

    popup.classList.remove('hide');

    messageContentBlock.textContent = message;

    //Reload files list to show changes
    getAllFilesRequest();

    //Refresh triggers
    document.querySelectorAll('.task-check')
        .forEach(el => el.addEventListener('change', setCommandsSelectHandlers));

    setTimeout(()=>{
        popup.classList.add('hide');
    }, 2000);
};