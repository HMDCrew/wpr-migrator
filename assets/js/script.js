
document.addEventListener("DOMContentLoaded", () => {


    const terminal = document.querySelector('.terminal-container .terminal-content');


    /**
     * It takes an element, a list of classes, and a string, and it adds or removes the classes from
     * the element, and then sets the element's innerText to the string
     * @param el - the element to be updated
     * @param class_list - an array of classes to add or remove from the element
     * @param text - The text to be displayed in the terminal.
     */
    const update_terminal_elment = (el, class_list, text) => {

        class_list.forEach(el_class => {
            if (el.classList.contains(el_class)) {
                el.classList.remove(el_class);
            } else {
                el.classList.add(el_class);
            }
        });

        el.innerText = text;
    }


    /**
     * It creates a terminal row element.
     * @param title - The title of the request
     * @param text_status - The status of the request.
     * @returns A div element with two child div elements.
     */
    const terminal_row = (title, text_status) => {

        let request_termin = document.createElement('div');
        request_termin.classList.add('d-flex');
        request_termin.classList.add('terminal-row');

        let status = document.createElement('div');
        let msg = document.createElement('div');

        update_terminal_elment(status, ['status', 'loading'], text_status);
        update_terminal_elment(msg, ['message'], title);

        request_termin.append(msg);
        request_termin.append(status);

        return request_termin;
    }


    /**
     * update terminal row where response status is success
     * @param response - The response object from the server.
     * @param [ter_row=null] - The row element of the terminal.
     */
    const update_terminal_row = (response, ter_row = null, class_list = ['loading']) => {
        if ('success' === response.status) {
            update_terminal_elment(ter_row.querySelector('.status'), class_list, 'completed');
        }
        if ('complete' === response.status) {
            update_terminal_elment(ter_row.querySelector('.status'), class_list, 'completed');
        }
    }


    /**
     * It returns a promise that resolves with the response of the request
     * @param url - The URL to send the request to.
     * @param method - The HTTP method to use, such as GET, POST, PUT, DELETE, etc.
     * @returns A promise
     */
    const sendHttpReq = async (url, data = {}, method = 'GET') => {
        return new Promise((resolve, reject) => {

            const xhr = new XMLHttpRequest();

            let new_url = new URL(url);
            if ('GET' === method) {
                Object.keys(data).map(key => {
                    new_url.searchParams.set(key, data[key]);
                });
            }

            xhr.open(method, new_url.href, true);
            xhr.setRequestHeader('Content-Type', 'application/json');

            xhr.onload = () => {
                resolve(xhr.response);
            }

            if ('GET' === method) {
                xhr.send();
            } else {
                xhr.send(JSON.stringify(data));
            }
        });
    }


    const btn_sync = document.querySelector('.btn-start-sync');
    btn_sync.addEventListener("click", () => {

        const site_key = document.querySelector('#save_info_migrator_key_authorization').value;
        const dest_site = document.querySelector('#save_info_migrator_new_domine').value;
        const dest_key = document.querySelector('#save_info_migrator_dest_key_authorization').value;

        const path_list = [
            '/wp-json/wpr-dump-database',
            '/wp-json/wpr-replace-domine-database',
            '/wp-json/wpr-compress-db',
            '/wp-json/wpr-compress-plugins',
            '/wp-json/wpr-compress-themes',
            '/wp-json/wpr-compress-uploads',
            '/wp-json/wpr-compress-wpcontent',
            '/wp-json/wpr-compress-wpcore',
        ];

        let pull_lev_1 = [];
        let pull_lev_2 = [];
        let override_url = '';
        path_list.forEach(async (url_path) => {

            const ter_row = terminal_row('local action ' + url_path, 'loading...');
            terminal.append(ter_row);

            pull_lev_1.push(
                sendHttpReq(window.location.origin + url_path, { key_site: site_key }).then(res => {

                    const response = JSON.parse(res);

                    update_terminal_row(response, ter_row);

                    if (response.hasOwnProperty('url') && 'success' === response.status) {

                        const downl_ter_row = terminal_row('migration ' + response.url + ' -- to --> ' + dest_site, 'loading...')
                        terminal.append(downl_ter_row);

                        pull_lev_2.push(
                            sendHttpReq(dest_site + 'wp-json/wpr-migrator-urls', { dest_key: dest_key, url: response.url }, 'POST').then(res => {

                                const migrator_res = JSON.parse(res);

                                if (migrator_res.hasOwnProperty('plugin_url')) {
                                    override_url = migrator_res.plugin_url;
                                }

                                update_terminal_row(
                                    JSON.parse(res),
                                    downl_ter_row
                                );
                            })
                        )
                    }
                })
            );
        });

        Promise.all(pull_lev_1).then(() => {
            Promise.all(pull_lev_2).then(async () => {

                const remove_local_bk = [
                    '/wp-json/wpr-remove-bk-db',
                    '/wp-json/wpr-remove-bk-plugins',
                    '/wp-json/wpr-remove-bk-themes',
                    '/wp-json/wpr-remove-bk-uploads',
                    '/wp-json/wpr-remove-bk-wpcontent',
                    '/wp-json/wpr-remove-bk-wpcore',
                ];
                remove_local_bk.forEach(async (el) => sendHttpReq(window.location.origin + el));

                const ter_row = terminal_row('site replacing on ' + dest_site, 'loading...')
                terminal.append(ter_row);

                sendHttpReq(override_url + 'class-systemovveride.php').then(res => {

                    const response = JSON.parse(res);

                    update_terminal_row(response, ter_row);

                    console.log(response);
                })
            })
        });

        terminal.style.height = "350px";

    }, false);
});
