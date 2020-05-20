let array = {id: 1, pid: 2};

for (let key in array) {
    if (!array.hasOwnProperty(key)) continue;
    console.log(key + "---" + array[key])
}
