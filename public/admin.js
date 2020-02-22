var admin = {
  ajax: function (opt) {
  // admin.ajax() : do AJAX call
  // PARAM opt : options

    // DATA
    var data = new FormData();
    for (var key in opt.data) {
      data.append(key, opt.data[key]);
    }

    // AJAX
    var xhr = new XMLHttpRequest();
    xhr.open('POST', "ajax-admin.php", true);
    xhr.onload = function () {
      if (typeof opt.load=="function") {
        opt.load(this.response);
      }
    };
    xhr.send(data);
  },

  list: function () {
  // admin.list() : show all parts

    admin.ajax({
      data : { req : "list" },
      load : function (res) {
        document.getElementById("container").innerHTML = res;
      }
    });
  },

  addEdit: function (part) {
  // admin.addEdit() : add/edit part docket
  // PARAM part : part number

    var data = {req : "addEdit"};
    if (part) { data.part = part; };
    admin.ajax({
      data : data,
      load : function (res) {
        document.getElementById("container").innerHTML = res;
      }
    });
  },

  save: function () {
  // admin.save() : save part

    // DATA
    var stock = document.getElementById("part_stock");
    var edit = stock==null;
    var data = {
      req : edit ? "edit" : "add",
      part : document.getElementById("part_no").value,
      name : document.getElementById("part_name").value,
      desc : document.getElementById("part_desc").value
    };
    if (edit) {
      data.oldPart = document.getElementById("part_old").value;
    } else {
      data.stock = stock.value;
    }

    // AJAX
    admin.ajax({
      data : data,
      load : function (res) {
        if (res=="OK") {
          admin.list();
        } else {
          alert(res);
        }
      }
    });
    return false;
  },

  del: function (part) {
  // admin.del() : delete part
  // PARAM part : part number

    if (confirm("Delete part?")) {
      admin.ajax({
        data : {
          req : "del",
          part : part
        },
        load : function (res) {
          if (res=="OK") {
            admin.list();
          } else {
            alert(res);
          }
        }
      });
    }
  }
};

var stock = {
  list : function (part) {
  // stock.list() : list the stock
  // PARAM part : part number

    admin.ajax({
      data : {
        req : "mvt-list",
        part: part
      },
      load : function (res) {
        document.getElementById("container").innerHTML = res;
      }
    });
  },

  add : function () {
  // stock.add : add new stock movement
  
    var part = document.getElementById("part_no").value;
    admin.ajax({
      data : {
        req : "mvt",
        type : document.getElementById("mvt_type").value,
        part: part,
        stock: document.getElementById("mvt_qty").value,
        comment: document.getElementById("mvt_comment").value
      },
      load : function (res) {
        if (res=="OK") {
          stock.list(part);
        } else {
          alert(res);
        }
      }
    });
    return false;
  }
};

window.addEventListener("load", admin.list);