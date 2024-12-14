import gi
import threading
import requests
import json
from gi.repository import Gtk, GLib, Gdk
import pynfc

gi.require_version('Gtk', '3.0')

class CampusVirtualClient(Gtk.Window):
    def __init__(self):
        super().__init__(title="Campus Virtual")
        self.set_default_size(600, 400)
        self.set_border_width(10)

        self.load_css("style.css")

        self.login_frame = Gtk.Box(orientation=Gtk.Orientation.VERTICAL, spacing=10)

        self.label_login = Gtk.Label(label="Aproxima al lector la vostra targeta")
        self.label_login.get_style_context().add_class("login-label")
        self.login_frame.pack_start(self.label_login, True, True, 0)

        self.add(self.login_frame)

        self.nfc = pynfc.Nfc()  
        self.start_nfc_thread()

    def load_css(self, css_file):
        css_provider = Gtk.CssProvider()
        css_provider.load_from_path(css_file)
        screen = Gdk.Screen.get_default()
        context = Gtk.StyleContext()
        context.add_provider_for_screen(
            screen, css_provider, Gtk.STYLE_PROVIDER_PRIORITY_APPLICATION
        )

    def start_nfc_thread(self):
        self.thread = threading.Thread(target=self.check_nfc)
        self.thread.daemon = True
        self.thread.start()

    def check_nfc(self):
        while True:
            print("Esperant a llegir la targeta...")
            try:
                target = next(self.nfc.poll())
                uid = target.uid.decode('ascii').upper()
                GLib.idle_add(self.handle_card_detected, uid)
                break
            except StopIteration:
                pass

    def handle_card_detected(self, uid):
        self.label_login.set_text(f"UID detected: {uid}")
        threading.Thread(target=self.query_student_name, args=(uid, self.handle_load_student_interface)).start()

    def query_student_name(self, uid, handler):
        student_url = f"http://172.20.10.6:8000/students?uid={uid}"
        try:
            response = requests.get(student_url)
            response.raise_for_status()
            data = response.json()
            name = data[0].get("name", "Unknown Student") if data and isinstance(data, list) else "Unknown Student"
            GLib.idle_add(handler, name, uid)
        except requests.RequestException:
            GLib.idle_add(self.label_login.set_text, "Error: Unable to fetch student name.")

    def handle_load_student_interface(self, name, uid):
        self.login_frame.destroy()
        self.student_frame = Gtk.Box(orientation=Gtk.Orientation.VERTICAL, spacing=10)
        self.label_welcome = Gtk.Label(label=f"Welcome {name}")
        self.label_welcome.get_style_context().add_class("welcome-label")
        self.student_frame.pack_start(self.label_welcome, False, False, 0)

        self.option_var = Gtk.ComboBoxText()
        self.option_var.append_text("Timetables")
        self.option_var.append_text("Tasks")
        self.option_var.append_text("Marks")
        self.option_var.set_active(0)
        self.student_frame.pack_start(self.option_var, False, False, 0)

        self.display_button = Gtk.Button(label="Show")
        self.display_button.get_style_context().add_class("show-button")
        self.display_button.connect("clicked", self.handle_display_button_clicked, uid)
        self.student_frame.pack_start(self.display_button, False, False, 0)

        self.data_view = Gtk.Box()
        self.student_frame.pack_start(self.data_view, True, True, 0)

        self.add(self.student_frame)
        self.show_all()

    def handle_display_button_clicked(self, button, uid):
        option = self.option_var.get_active_text().lower()
        for child in self.data_view.get_children():
            self.data_view.remove(child)
        threading.Thread(target=self.query_server, args=(option, uid)).start()

    def query_server(self, option, uid):
        server_url = f"http://172.20.10.6:8000/{option}?uid={uid}"
        try:
            response = requests.get(server_url)
            response.raise_for_status()
            data = response.json()

            if option == "timetables":
                columns = ["day", "hour", "subject", "room"]
            elif option == "tasks":
                columns = ["date", "subject", "name"]
            elif option == "marks":
                columns = ["subject", "name", "mark"]
            else:
                raise ValueError("Invalid option selected")

            GLib.idle_add(self.handle_display_table, data, columns)

        except requests.RequestException as e:
            GLib.idle_add(self.handle_display_data, f"Error loading {option}:\n{e}")
        except json.JSONDecodeError:
            GLib.idle_add(self.handle_display_data, f"Error: Server did not return valid JSON.")

    def handle_display_table(self, data, columns):
        store = Gtk.ListStore(*(str for _ in columns))
        for entry in data:
            row = [entry.get(col, "") for col in columns]
            store.append(row)
        treeview = Gtk.TreeView(model=store)
        for i, column_title in enumerate(columns):
            renderer = Gtk.CellRendererText()
            column = Gtk.TreeViewColumn(column_title.capitalize(), renderer, text=i)
            treeview.append_column(column)
        scrolled_window = Gtk.ScrolledWindow()
        scrolled_window.add(treeview)
        scrolled_window.set_min_content_height(200)
        self.data_view.pack_start(scrolled_window, True, True, 0)
        self.show_all()

    def handle_display_data(self, data):
        text_view = Gtk.TextView()
        text_buffer = Gtk.TextBuffer()
        text_view.set_buffer(text_buffer)
        text_buffer.set_text(data)
        self.data_view.pack_start(text_view, True, True, 0)
        self.show_all()

if __name__ == "__main__":
    app = CampusVirtualClient()
    app.connect("destroy", Gtk.main_quit)
    app.show_all()
    Gtk.main()
