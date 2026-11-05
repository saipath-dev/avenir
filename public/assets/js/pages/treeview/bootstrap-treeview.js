// base url
var base_url = window.location.origin + "/avenir"; // base url

// var defaultData = [
//   {
//     text: "Parent 1",
//     href: "#parent1",
//     tags: ["4"],
//     nodes: [
//       {
//         text: "Child 1",
//         href: "#child1",
//         tags: ["2"],
//         nodes: [
//           {
//             text: "Grandchild 1",
//             href: "#grandchild1",
//             tags: ["0"],
//           },
//           {
//             text: "Grandchild 2",
//             href: "#grandchild2",
//             tags: ["0"],
//           },
//         ],
//       },
//       {
//         text: "Child 2",
//         href: "#child2",
//         tags: ["0"],
//       },
//     ],
//   },
//   {
//     text: "Parent 2",
//     href: "#parent2",
//     tags: ["0"],
//   },
// ];

var initSelectableTree = function () {
  var defaultData = [];

  $.ajax({
    url: base_url + "/accounts/get_accounts_list",
    type: "GET",
    success: function (response) {
      // convert response to json
      response = JSON.parse(response);

      var base_accounts = response.base_accounts;
      var heading_accounts = response.heading_accounts;
      var title_accounts = response.title_accounts;

      // foreach base accounts
      base_accounts.forEach(function (base_account) {
        var base_account_data = {
          text: base_account.BName.toUpperCase(),
          href: base_url + "/accounts?BCode=" + base_account.B_Code + "&path=" + base_account.BName + "&codes=" + base_account.B_Code,
          tags: ["0"],
        };
        // foreach heading accounts
        var title_accounts_data = [];
        title_accounts.forEach(function (title_account) {
          if (title_account.B_Code == base_account.B_Code) {
            var title_account_data = {
              text: title_account.TName.toUpperCase(),
              href: base_url + "/accounts/title_accounts?BCode=" + title_account.B_Code + "&TCode=" + title_account.T_Code + "&path=" + base_account.BName + "//" + title_account.TName + "&codes=" + base_account.B_Code + "//" + title_account.T_Code,
              tags: ["0"],
            };
            // foreach title accounts
            var heading_accounts_data = [];
            heading_accounts.forEach(function (heading_account) {
              if (
                title_account.T_Code == heading_account.T_Code &&
                base_account.B_Code == heading_account.B_Code
              ) {
                var heading_account_data = {
                  text: heading_account.HName.toUpperCase(),
                  tags: ["0"],
				  href: base_url + "/accounts/heading_accounts?BCode=" + title_account.B_Code + "&TCode=" + title_account.T_Code + "&HCode=" + heading_account.H_Code + "&path=" + base_account.BName + "//" + title_account.TName + "//" + heading_account.HName + "&codes=" + base_account.B_Code + "//" + title_account.T_Code + "//" + heading_account.H_Code,
              tags: ["0"],
                };
                heading_accounts_data.push(heading_account_data);
              }
            });
            title_account_data.nodes = heading_accounts_data;
            title_accounts_data.push(title_account_data);
          }
        });
        base_account_data.nodes = title_accounts_data;
        defaultData.push(base_account_data);
      });

      //   $("#treeview3").treeview({
      //     levels: 1,
      //     expandIcon: "icon-arrow-right",
      //     collapseIcon: "icon-arrow-down",
      //     showBorder: true,
      //     data: defaultData,
      //   });
      return $("#treeview8").treeview({
        levels: 1,
        data: defaultData,
        showBorder: false,
        expandIcon: "bi bi-chevron-right",
        collapseIcon: "bi bi-chevron-down",
        multiSelect: $("#chk-select-multi").is(":checked"),
        onNodeSelected: function (event, node) {
          window.location.href = node.href;
        },
        onNodeUnselected: function (event, node) {
          // $('#selectable-output').prepend('<p>' + node.text + ' was unselected</p>');
          //   toastr.options.closeButton = true;
          //   toastr.options.positionClass = "toast-top-right";
          //   toastr.options.showDuration = 1000;
          //   toastr["error"](node.text + " was unselected");
        },
      });
    },
    error: function (e) {
      alert("Error loading chart of accounts data. Please refresh the page.");
    },
  });
};
var $selectableTree = initSelectableTree();
