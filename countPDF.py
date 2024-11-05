# importing required modules
from PyPDF2 import PdfReader
import sys


def main(file):
    # creating a pdf reader object
    reader = PdfReader(file)

    # printing number of pages in pdf file
    return str(len(reader.pages))


if __name__ == '__main__':
    main(sys.argv[1])
