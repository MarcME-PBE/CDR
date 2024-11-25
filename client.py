import gi
import time
import threading
import requests
from gi.repository import Gtk, GLib, Gdk
from rpi_lcd import LCD
from PUZZLE1_ANNA import RfidPyNFC

gi.require_version('Gtk', '3.0')

class CampusVirtualClient(Gtk.Window):
    def __init__(self):
        super().__init__(title="Campus Virtual")
        self.set_default_size(400, 300)
        self.set_border_width(10)

        self.login_frame = Gtk.Box(orientation=Gtk.Orientation.VERTICAL, spacing=10)
        self.label_login = Gtk.Label(label="Please, login with your university card")
        self.label_login.override_background_color(Gtk.StateFlags.NORMAL, Gdk.RGBA(0, 0, 1, 1))  # Fondo azul
        self.label_login.override_color(Gtk.StateFlags.NORMAL, Gdk.RGBA(1, 1, 1, 1))  # Texto blanco
        self.login_frame.pack_start(self.label_login, True, True, 0)

        self.add(self.login_frame)
        
        self.nfc_reader = RfidPyNFC()
        self.lcd = LCD()
        self.start_nfc_thread()

    def start_nfc_thread(self):
        self.thread = threading.Thread(target=self.check_nfc)
        self.thread.daemon = True
        self.thread.start()

    def check_nfc(self):
        while True:
            uid = self.nfc_reader.read_uid()
            if uid:
                GLib.idle_add(self.on_card_detected, uid)
                break

    def on_card_detected(self, uid):
        self.label_login.set_text(f"UID detected: {uid}")
        self.label_login.override_background_color(Gtk.StateFlags.NORMAL, Gdk.RGBA(0, 1, 0, 1))

        self.load_student_interface(uid)

    def load_student_interface(self, uid):
        self.login_frame.destroy()

        self.student_frame = Gtk.Box(orientation=Gtk.Orientation.VERTICAL, spacing=10)

        self.lcd.clear()
        self.lcd.text("Welcome", 1)
        self.lcd.text(uid, 2) 

        self.option_var = Gtk.ComboBoxText()
        self.option_var.append_text("Timetable")
        self.option_var.append_text("Tasks")
        self.option_var.append_text("Marks")
        self.option_var.set_active(0)
        self.student_frame.pack_start(self.option_var, False, False, 0)

        self.display_button = Gtk.Button(label="Show")
        self.display_button.connect("clicked", self.on_display_button_clicked)
        self.student_frame.pack_start(self.display_button, False, False, 0)

        self.data_view = Gtk.TextView()
        self.data_buffer = Gtk.TextBuffer()
        self.data_view.set_buffer(self.data_buffer)
        scrolled_window = Gtk.ScrolledWindow()
        scrolled_window.add(self.data_view)
        scrolled_window.set_min_content_height(150)
        self.student_frame.pack_start(scrolled_window, True, True, 0)

        self.add(self.student_frame)
        self.show_all()

    def on_display_button_clicked(self, button):
        option = self.option_var.get_active_text().lower()

        server = f"http://10.192.91.240:8000/{option}"
        try:
            response = requests.get(server)
            response.raise_for_status()
            data = response.text
            self.display_data(data)

            self.lcd.clear()
            self.lcd.text(option.capitalize(), 1)
            lines = data.split('\n')[:3]
            for i, line in enumerate(lines):
                self.lcd.text(line[:20], i + 2)
        except requests.RequestException as e:
            self.display_data(f"Error loading {option}:\n{e}")

    def display_data(self, data):
        self.data_buffer.set_text(data)

if __name__ == "__main__":
    app = CampusVirtualClient()
    app.connect("destroy", Gtk.main_quit)
    app.show_all()
    Gtk.main()

